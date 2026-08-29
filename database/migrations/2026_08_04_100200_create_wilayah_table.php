<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wilayah administratif untuk dropdown alamat (cakupan Malang Raya:
     * Kota Malang, Kabupaten Malang, Kota Batu). Satu baris per desa/kelurahan.
     */
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->id();
            $table->string('kota');
            $table->string('kecamatan');
            $table->string('desa_kelurahan');
            $table->unique(['kota', 'kecamatan', 'desa_kelurahan'], 'wilayah_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
