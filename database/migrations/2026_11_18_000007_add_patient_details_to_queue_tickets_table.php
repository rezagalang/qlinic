<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable agar tiket yang sudah ada tetap valid.
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->string('gender', 1)->nullable()->after('birth_date'); // L | P
            $table->string('address')->nullable()->after('gender');
            $table->text('complaint')->nullable()->after('address');      // keluhan / tujuan periksa
        });
    }

    public function down(): void
    {
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->dropColumn(['gender', 'address', 'complaint']);
        });
    }
};
