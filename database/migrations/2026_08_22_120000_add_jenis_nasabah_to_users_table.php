<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // perorangan = BSIL-XXXX, corporate = CORP-XXXX
            $table->string('jenis_nasabah', 20)->default('perorangan')->after('role');
            $table->index(['role', 'jenis_nasabah'], 'users_role_jenis_index');
        });

        // Backfill existing nasabah: semua yang sudah ada dianggap perorangan (BSIL)
        // Jika ada kode_nasabah dengan prefix CORP, anggap corporate (jaga kalau data manual)
        DB::table('users')
            ->where('role', 'nasabah')
            ->whereNull('jenis_nasabah')
            ->orWhere('jenis_nasabah', '')
            ->update(['jenis_nasabah' => 'perorangan']);

        DB::table('users')
            ->where('role', 'nasabah')
            ->where('kode_nasabah', 'like', 'CORP-%')
            ->update(['jenis_nasabah' => 'corporate']);

        // Pastikan nasabah yang kode BSIL tetap perorangan
        DB::table('users')
            ->where('role', 'nasabah')
            ->where('kode_nasabah', 'like', 'BSIL-%')
            ->update(['jenis_nasabah' => 'perorangan']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_jenis_index');
            $table->dropColumn('jenis_nasabah');
        });
    }
};
