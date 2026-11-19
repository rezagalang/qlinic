<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // nama dokter / klinik
            $table->string('specialty')->nullable();      // mis. "Klinik Anak"
            $table->string('address')->nullable();
            $table->string('whatsapp', 20)->nullable();   // format internasional tanpa +, mis. 6285239116000
            $table->string('whatsapp_label')->nullable(); // tampilan, mis. 0852 3911 6000
            $table->text('maps_url')->nullable();         // link petunjuk arah
            $table->text('map_embed_url')->nullable();
            $table->string('footer_text')->nullable();
            $table->unsignedTinyInteger('registration_hours_before')->default(3); // pendaftaran dibuka N jam sebelum praktik
            $table->unsignedTinyInteger('booking_days_ahead')->default(4);        // jumlah hari yang bisa dipilih
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
