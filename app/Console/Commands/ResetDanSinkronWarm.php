<?php

namespace App\Console\Commands;

use App\Models\HargaSampah;
use App\Models\KategoriSampah;
use App\Models\KelompokSampah;
use App\Models\Setoran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ResetDanSinkronWarm extends Command
{
    protected $signature = 'warm:reset-dan-sinkron {--sqlite : Jalankan juga pada koneksi SQLite lokal}';

    protected $description = 'Kosongkan setoran, harga, dan kategori sampah, lalu sinkronkan 61 kelompok EPA WARM v16';

    public function handle(): int
    {
        $this->info('Memulai reset data sampah dan sinkronisasi kelompok EPA WARM v16...');

        $jsonPath = base_path('tools/warm-calculator/data/warm_v16_catalog_full.json');
        if (! file_exists($jsonPath)) {
            $this->error("File katalog WARM tidak ditemukan di: {$jsonPath}");

            return self::FAILURE;
        }

        $materials = json_decode(file_get_contents($jsonPath), true) ?: [];
        $this->info('Memuat ' . count($materials) . ' material dari katalog WARM v16.');

        $connections = [config('database.default')];
        if ($this->option('sqlite') && config('database.default') !== 'sqlite') {
            $connections[] = 'sqlite';
        }

        foreach ($connections as $conn) {
            $this->info("Memproses koneksi: [{$conn}]...");

            if ($conn === 'sqlite') {
                config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
            }

            DB::connection($conn)->transaction(function () use ($conn, $materials) {
                // 1. Kosongkan setoran, harga_sampah, kategori_sampah
                Setoran::on($conn)->delete();
                HargaSampah::on($conn)->delete();
                KategoriSampah::on($conn)->delete();
                KelompokSampah::on($conn)->delete();

                $driver = DB::connection($conn)->getDriverName();

                if ($driver === 'pgsql') {
                    DB::connection($conn)->statement("SELECT setval(pg_get_serial_sequence('setoran', 'id'), 1, false)");
                    DB::connection($conn)->statement("SELECT setval(pg_get_serial_sequence('harga_sampah', 'id'), 1, false)");
                    DB::connection($conn)->statement("SELECT setval(pg_get_serial_sequence('kategori_sampah', 'id'), 1, false)");
                    DB::connection($conn)->statement("SELECT setval(pg_get_serial_sequence('kelompok_sampah', 'id'), 1, false)");
                } elseif ($driver === 'sqlite') {
                    DB::connection($conn)->statement("DELETE FROM sqlite_sequence WHERE name IN ('setoran', 'harga_sampah', 'kategori_sampah', 'kelompok_sampah')");
                }

                // 2. Insert 61 Kelompok Sampah WARM v16
                $rows = [];
                $now = now();
                foreach ($materials as $i => $m) {
                    $rows[] = [
                        'id' => $i + 1,
                        'kode' => $m['kode'],
                        'nama' => $m['nama'],
                        'deskripsi' => ($m['grup'] ?? 'Lainnya') . ' — ' . $m['warm_name'],
                        'urutan' => $i + 1,
                        'default_recycled_content' => 0,
                        'ef_virgin' => 0,
                        'ef_recycled' => (float) ($m['ef'] ?? 0.0),
                        'ef_current' => 0,
                        'forest_c_seq' => 0,
                        'energy_virgin' => 0,
                        'energy_recycled' => (float) ($m['kwh'] ?? 0.0),
                        'energy_current' => 0,
                        'landfilling_ef' => 0.02,
                        'combustion_ef' => 0,
                        'energy_landfilling_ef' => 0,
                        'energy_combustion_ef' => 0,
                        'loss_rate' => 1.0,
                        'aktif' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::connection($conn)->table('kelompok_sampah')->insert($rows);

                if ($driver === 'pgsql') {
                    DB::connection($conn)->statement("SELECT setval(pg_get_serial_sequence('kelompok_sampah', 'id'), " . count($rows) . ", true)");
                }
            });

            $this->info("Koneksi [{$conn}] berhasil disinkronkan dengan " . count($materials) . ' kelompok WARM v16.');
        }

        // Flush application cache & memo
        Cache::flush();
        KategoriSampah::lupakanSemuaMemoHarga();

        $this->info('Semua data setoran dan harga telah di-reset ke 0, dan 61 kelompok EPA WARM v16 telah aktif.');

        return self::SUCCESS;
    }
}
