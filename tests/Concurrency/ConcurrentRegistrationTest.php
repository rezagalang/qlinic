<?php

namespace Tests\Concurrency;

use App\Models\PracticeSchedule;
use App\Models\QueueTicket;
use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Uji konkurensi nyata: banyak proses PHP mendaftar bersamaan ke MySQL.
 *
 * Tidak ikut `phpunit.xml` bawaan (SQLite tidak mendukung row lock). Jalankan dengan:
 *   vendor/bin/phpunit -c phpunit.mysql.xml
 * Database HARUS bernama *_test karena tabelnya di-migrate:fresh.
 */
class ConcurrentRegistrationTest extends TestCase
{
    use DatabaseMigrations;

    private const NOW = '2026-11-18 08:00:00'; // Rabu, sesi Pagi terbuka

    protected function setUp(): void
    {
        parent::setUp();

        $database = (string) config('database.connections.'.config('database.default').'.database');

        if (config('database.default') !== 'mysql' || ! str_ends_with($database, '_test')) {
            $this->markTestSkipped('Butuh MySQL dengan nama database berakhiran _test (lihat phpunit.mysql.xml).');
        }

        $this->seed(ClinicSeeder::class);
    }

    /** @return array<int, string> keluaran stdout tiap proses */
    private function race(int $count): array
    {
        $startAt = microtime(true) + 4.0; // beri waktu semua proses selesai boot

        $results = Process::path(base_path())->timeout(120)->pool(function (Pool $pool) use ($count, $startAt) {
            for ($i = 1; $i <= $count; $i++) {
                $pool->command([
                    PHP_BINARY, 'artisan', 'qlinic:probe-register', '2026-11-18', 'pagi', (string) $i,
                    '--now='.self::NOW, '--start-at='.$startAt,
                ]);
            }
        })->start()->wait();

        $outputs = [];
        foreach ($results as $result) {
            $outputs[] = trim($result->output());
        }

        return $outputs;
    }

    public function test_parallel_registrations_never_share_a_queue_number(): void
    {
        $outputs = $this->race(15);

        $numbers = collect($outputs)
            ->filter(fn (string $o) => str_starts_with($o, 'NUMBER='))
            ->map(fn (string $o) => (int) substr($o, 7))
            ->sort()
            ->values();

        $this->assertCount(15, $numbers, 'Semua pendaftaran seharusnya berhasil: '.implode(' | ', $outputs));
        $this->assertSame(range(1, 15), $numbers->all(), 'Nomor harus 1..15 tanpa duplikat dan tanpa lubang.');
        $this->assertSame(15, QueueTicket::count());
    }

    public function test_parallel_registrations_never_exceed_the_quota(): void
    {
        PracticeSchedule::where('day_of_week', 3)->where('session', 'pagi')->update(['quota' => 5]);

        $outputs = $this->race(12);

        $accepted = collect($outputs)->filter(fn (string $o) => str_starts_with($o, 'NUMBER='));
        $rejected = collect($outputs)->filter(fn (string $o) => str_starts_with($o, 'REJECTED='));

        $this->assertCount(5, $accepted, implode(' | ', $outputs));
        $this->assertCount(7, $rejected);
        $this->assertSame(5, QueueTicket::count());
        $this->assertSame([1, 2, 3, 4, 5], QueueTicket::orderBy('number')->pluck('number')->all());
    }
}