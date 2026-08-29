<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * R5: jaminan level-DB bahwa berat & uang tidak pernah negatif pada tabel setoran
 * (integritas tabungan nasabah).
 *
 * Hanya Postgres/Supabase yang butuh & didukung:
 * - Postgres: `unsignedInteger` menjadi signed `integer`, jadi negatif MUNGKIN tanpa CHECK.
 * - MySQL/MariaDB: kolom `unsigned` sudah menolak negatif di level tipe.
 * - SQLite (dev/test): tak mendukung ADD CONSTRAINT pada tabel yang sudah ada tanpa rebuild
 *   penuh; dev/test bergantung pada validasi service layer. Migrasi no-op di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'ALTER TABLE setoran ADD CONSTRAINT setoran_nilai_tidak_negatif '
            .'CHECK (berat_gram > 0 AND harga_per_kg >= 0 AND total_rupiah >= 0)'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE setoran DROP CONSTRAINT IF EXISTS setoran_nilai_tidak_negatif');
    }
};
