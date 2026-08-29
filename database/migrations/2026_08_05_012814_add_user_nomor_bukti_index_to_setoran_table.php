<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('setoran', function (Blueprint $table) {
            // Mendukung query groupBy beranda nasabah:
            // filter user_id lalu groupBy nomor_bukti.
            $table->index(['user_id', 'nomor_bukti']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setoran', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'nomor_bukti']);
        });
    }
};
