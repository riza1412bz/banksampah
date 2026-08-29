<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kelompok sampah = kategori material EPA ReCon Tool (Aluminum Cans, Steel
 * Cans, Glass, HDPE, PET, Mixed Plastics, dst) beserta faktor dampak
 * lingkungannya. Kategori sampah ditautkan opsional ke satu kelompok supaya
 * halaman harga bisa ditampilkan per kelompok dan kalkulator dampak
 * lingkungan nanti bisa dihitung per kategori.
 *
 * Satuan: ef_* = MTCO2e/ton, energy_* = MMBtu/ton,
 * default_recycled_content = persen konten daur ulang bawaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelompok_sampah', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama');
            $table->string('deskripsi', 255)->nullable();
            $table->unsignedInteger('urutan');
            $table->decimal('default_recycled_content', 6, 2);
            $table->decimal('ef_virgin', 12, 6);
            $table->decimal('ef_recycled', 12, 6);
            $table->decimal('ef_current', 12, 6);
            $table->decimal('forest_c_seq', 12, 6);
            $table->decimal('energy_virgin', 12, 4);
            $table->decimal('energy_recycled', 12, 4);
            $table->decimal('energy_current', 12, 4);
            $table->decimal('landfilling_ef', 12, 6);
            $table->decimal('combustion_ef', 12, 6);
            $table->decimal('loss_rate', 8, 4);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::table('kategori_sampah', function (Blueprint $table) {
            $table->foreignId('kelompok_sampah_id')
                ->nullable()
                ->after('id')
                ->constrained('kelompok_sampah')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kategori_sampah', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelompok_sampah_id');
        });
        Schema::dropIfExists('kelompok_sampah');
    }
};
