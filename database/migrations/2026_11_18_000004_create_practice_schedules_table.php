<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jadwal praktik mingguan (berulang)
        Schema::create('practice_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = Senin ... 7 = Minggu (ISO)
            $table->string('session', 10);              // pagi | sore | malam
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('quota')->nullable(); // null = tanpa batas
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['clinic_id', 'day_of_week', 'session']);
        });

        // Pengecualian tanggal tertentu (libur / jadwal khusus)
        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('session', 10)->nullable(); // null = semua sesi pada tanggal tsb
            $table->boolean('is_closed')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['clinic_id', 'date', 'session']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_exceptions');
        Schema::dropIfExists('practice_schedules');
    }
};
