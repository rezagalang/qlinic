<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Service;
use App\Support\DeviceToken;
use Carbon\Carbon;
use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
        $this->travelTo(Carbon::parse('2026-11-18 08:00:00')); // Rabu, sesi Pagi terbuka
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'date' => '2026-11-18',
            'session' => 'pagi',
            'service_id' => Service::first()->id,
            'patient_name' => 'Budi',
            'phone' => '0812 3456 7890',
            'birth_date' => '2020-05-01',
            'gender' => 'L',
            'address' => 'Jl. Mawar No. 1, Banyuwangi',
            'complaint' => 'Demam dan batuk',
        ], $override);
    }

    public function test_registration_form_opens_for_an_open_session(): void
    {
        $this->get('/antrian/daftar?date=2026-11-18&session=pagi')
            ->assertOk()
            ->assertSee('Nama Pasien')
            ->assertSee('Keluhan/Tujuan Periksa');
    }

    public function test_registration_form_redirects_for_a_closed_session(): void
    {
        $this->get('/antrian/daftar?date=2026-11-18&session=sore')->assertRedirect('/');
    }

    public function test_patient_can_register_and_receives_device_cookie(): void
    {
        $this->post('/antrian', $this->payload())
            ->assertRedirect('/antrian')
            ->assertCookie(DeviceToken::COOKIE);

        $this->assertDatabaseHas('queue_tickets', ['number' => 1, 'phone' => '081234567890', 'status' => 'menunggu']);
    }

    public function test_numbers_increment_per_date_and_session(): void
    {
        $this->post('/antrian', $this->payload());
        $this->post('/antrian', $this->payload(['birth_date' => '2022-02-02', 'patient_name' => 'Adik']));

        // Sesi Malam (mulai 19:00) baru dibuka 3 jam sebelumnya, yaitu 16:00.
        $this->travelTo(Carbon::parse('2026-11-18 17:00:00'));
        $this->post('/antrian', $this->payload(['session' => 'malam', 'birth_date' => '2021-03-03']));

        $this->assertSame([1, 2], QueueTicket::where('session', 'pagi')->orderBy('number')->pluck('number')->all());
        $this->assertSame([1], QueueTicket::where('session', 'malam')->pluck('number')->all());
    }

    public function test_same_phone_and_birth_date_cannot_register_twice_in_one_session(): void
    {
        $this->post('/antrian', $this->payload());
        $this->post('/antrian', $this->payload())->assertSessionHasErrors('phone');

        $this->assertSame(1, QueueTicket::count());
    }

    public function test_closed_session_is_rejected_on_the_server(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 13:00:00'));

        $this->post('/antrian', $this->payload())->assertSessionHasErrors('session');
        $this->assertSame(0, QueueTicket::count());
    }

    public function test_patient_details_are_saved(): void
    {
        $this->post('/antrian', $this->payload(['gender' => 'P']))->assertRedirect('/antrian');

        $this->assertDatabaseHas('queue_tickets', [
            'gender' => 'P',
            'address' => 'Jl. Mawar No. 1, Banyuwangi',
            'complaint' => 'Demam dan batuk',
        ]);
    }

    public function test_complaint_is_optional_but_gender_and_address_are_required(): void
    {
        $this->post('/antrian', $this->payload(['complaint' => '']))->assertRedirect('/antrian');
        $this->assertNull(QueueTicket::first()->complaint);

        $this->post('/antrian', $this->payload(['birth_date' => '2019-09-09', 'gender' => 'X']))->assertSessionHasErrors('gender');
        $this->post('/antrian', $this->payload(['birth_date' => '2019-09-09', 'address' => '']))->assertSessionHasErrors('address');
    }

    public function test_impossible_birth_date_is_rejected(): void
    {
        $this->post('/antrian', $this->payload(['birth_date' => '2020-02-31']))->assertSessionHasErrors('birth_date');
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->post('/antrian', $this->payload(['phone' => '12345']))->assertSessionHasErrors('phone');
    }

    public function test_my_queue_lists_tickets_for_the_device(): void
    {
        $token = str_repeat('t', 40);
        $this->withCookie(DeviceToken::COOKIE, $token)->post('/antrian', $this->payload());

        $this->withCookie(DeviceToken::COOKIE, $token)
            ->get('/antrian')
            ->assertOk()
            ->assertSee('Budi')
            ->assertDontSee('Anda belum memiliki nomor antrian');
    }

    public function test_ticket_can_be_recovered_with_phone_and_birth_date(): void
    {
        $this->withCookie(DeviceToken::COOKIE, str_repeat('o', 40))->post('/antrian', $this->payload());

        $this->withCookie(DeviceToken::COOKIE, str_repeat('n', 40))
            ->post('/antrian/pulihkan', ['phone' => '+6281234567890', 'birth_date' => '2020-05-01'])
            ->assertRedirect('/antrian');

        $this->assertSame(str_repeat('n', 40), QueueTicket::first()->device_token);
    }

    public function test_recovery_fails_with_wrong_birth_date(): void
    {
        $this->post('/antrian', $this->payload());

        $this->post('/antrian/pulihkan', ['phone' => '081234567890', 'birth_date' => '2019-01-01'])
            ->assertSessionHasErrors('phone', null, 'recover');
    }
}