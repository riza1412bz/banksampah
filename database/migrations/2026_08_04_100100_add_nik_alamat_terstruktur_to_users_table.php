<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NIK (wajib untuk nasabah, unik) + alamat terstruktur:
     * kota, kecamatan, desa/kelurahan, jalan, RT/RW, detail rumah (opsional).
     * Kolom lama `alamat` (textarea bebas) dipertahankan untuk riwayat.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->unique()->after('kode_nasabah');
            $table->string('kota')->nullable()->after('alamat');
            $table->string('kecamatan')->nullable()->after('kota');
            $table->string('desa_kelurahan')->nullable()->after('kecamatan');
            $table->string('jalan')->nullable()->after('desa_kelurahan');
            $table->string('rt_rw', 10)->nullable()->after('jalan');
            $table->string('detail_rumah')->nullable()->after('rt_rw');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'kota', 'kecamatan', 'desa_kelurahan', 'jalan', 'rt_rw', 'detail_rumah']);
        });
    }
};
