<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- setoran: indeks penunjang dashboard & laporan ---
        Schema::table('setoran', function (Blueprint $table) {
            // Untuk query perKategori: WHERE tanggal_setor BETWEEN ? AND ? GROUP BY kategori_sampah_id
            if (! $this->hasIndex('setoran', 'setoran_tanggal_kategori_index')) {
                $table->index(['tanggal_setor', 'kategori_sampah_id'], 'setoran_tanggal_kategori_index');
            }
            // Lookup kategori_sampah_id standalone (FK restrict)
            if (! $this->hasIndex('setoran', 'setoran_kategori_id_index')) {
                $table->index('kategori_sampah_id', 'setoran_kategori_id_index');
            }
            // Covering untuk LaporanSetoran agregat: groupBy nomor_bukti dengan filter tanggal
            if (! $this->hasIndex('setoran', 'setoran_tanggal_nomor_bukti_index')) {
                $table->index(['tanggal_setor', 'nomor_bukti'], 'setoran_tanggal_nomor_bukti_index');
            }
        });

        // --- harga_sampah: mempercepat hargaAktif() (whereNull berlaku_sampai) ---
        Schema::table('harga_sampah', function (Blueprint $table) {
            if (! $this->hasIndex('harga_sampah', 'harga_kategori_berlaku_index')) {
                $table->index(['kategori_sampah_id', 'berlaku_sampai', 'berlaku_dari'], 'harga_kategori_berlaku_index');
            }
        });

        // --- users: filter role+aktif yang dipakai di banyak controller ---
        Schema::table('users', function (Blueprint $table) {
            if (! $this->hasIndex('users', 'users_role_aktif_index')) {
                $table->index(['role', 'aktif'], 'users_role_aktif_index');
            }
            if (! $this->hasIndex('users', 'users_nama_index')) {
                $table->index('name', 'users_nama_index');
            }
        });

        // --- jadwal_setor: filter untukNasabah + mendatang/sudahLewat ---
        Schema::table('jadwal_setor', function (Blueprint $table) {
            if (! $this->hasIndex('jadwal_setor', 'jadwal_user_tanggal_index')) {
                $table->index(['user_id', 'tanggal'], 'jadwal_user_tanggal_index');
            }
            if (! $this->hasIndex('jadwal_setor', 'jadwal_tanggal_index')) {
                $table->index('tanggal', 'jadwal_tanggal_index');
            }
        });

        // --- kategori_sampah: filter aktif ---
        Schema::table('kategori_sampah', function (Blueprint $table) {
            if (! $this->hasIndex('kategori_sampah', 'kategori_aktif_index')) {
                $table->index('aktif', 'kategori_aktif_index');
            }
        });

        // SQLite: aktifkan WAL secara permanen (jika belum)
        if (DB::getDriverName() === 'sqlite') {
            try {
                DB::statement('PRAGMA journal_mode=WAL');
                DB::statement('PRAGMA synchronous=NORMAL');
                DB::statement('PRAGMA busy_timeout=5000');
            } catch (\Throwable $e) {
                // abaikan jika tidak didukung
            }
        }
    }

    public function down(): void
    {
        Schema::table('setoran', function (Blueprint $table) {
            $table->dropIndex('setoran_tanggal_kategori_index');
            $table->dropIndex('setoran_kategori_id_index');
            $table->dropIndex('setoran_tanggal_nomor_bukti_index');
        });
        Schema::table('harga_sampah', function (Blueprint $table) {
            $table->dropIndex('harga_kategori_berlaku_index');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_aktif_index');
            $table->dropIndex('users_nama_index');
        });
        Schema::table('jadwal_setor', function (Blueprint $table) {
            $table->dropIndex('jadwal_user_tanggal_index');
            $table->dropIndex('jadwal_tanggal_index');
        });
        Schema::table('kategori_sampah', function (Blueprint $table) {
            $table->dropIndex('kategori_aktif_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return Schema::hasIndex($table, $index);
    }
};
