<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah faktor disposal ENERGI (MMBtu/ton) ke kelompok_sampah.
 * Rekanan GHG-nya (landfilling_ef/combustion_ef) sudah ada di migration
 * pembuatan tabel. Faktor ini dipakai kalkulator dampak lingkungan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelompok_sampah', function (Blueprint $table) {
            $table->decimal('energy_landfilling_ef', 12, 4)->nullable()->after('combustion_ef');
            $table->decimal('energy_combustion_ef', 12, 4)->nullable()->after('energy_landfilling_ef');
        });
    }

    public function down(): void
    {
        Schema::table('kelompok_sampah', function (Blueprint $table) {
            $table->dropColumn(['energy_landfilling_ef', 'energy_combustion_ef']);
        });
    }
};
