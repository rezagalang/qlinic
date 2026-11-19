<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->date('practice_date');
            $table->string('session', 10);                 // pagi | sore | malam
            $table->unsignedInteger('number');             // urut per tanggal + sesi
            $table->string('patient_name');
            $table->string('phone', 20);
            $table->date('birth_date');
            $table->string('device_token', 64)->index();   // cookie perangkat pasien
            $table->string('status', 15)->default('menunggu'); // menunggu | dipanggil | selesai | dilewati | dibatalkan
            $table->timestamp('called_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // mencegah nomor ganda pada tanggal + sesi yang sama
            $table->unique(['clinic_id', 'practice_date', 'session', 'number']);
            // dipakai untuk pemulihan nomor (HP + tanggal lahir) dan daftar antrian harian
            $table->index(['phone', 'birth_date']);
            $table->index(['practice_date', 'session', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
