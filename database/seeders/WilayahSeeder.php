<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeder wilayah administratif cakupan Malang Raya (Kota Malang,
 * Kabupaten Malang, Kota Batu) dari data statis JSON yang diambil dari
 * API resmi emsifa api-wilayah-indonesia.
 *
 * Struktur JSON: { "kota_malang": { "KECAMATAN": ["Desa", ...] }, ... }
 */
class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/wilayah_malang_raya.json');

        if (! File::exists($path)) {
            $this->command?->warn("Data wilayah tidak ditemukan: {$path}");

            return;
        }

        $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        $baris = [];
        foreach ($data as $kota => $kecamatan) {
            $namaKota = strtoupper(str_replace('_', ' ', $kota));

            foreach ($kecamatan as $namaKecamatan => $desa) {
                foreach ($desa as $namaDesa) {
                    $baris[] = [
                        'kota' => $namaKota,
                        'kecamatan' => $namaKecamatan,
                        'desa_kelurahan' => $namaDesa,
                    ];
                }
            }
        }

        Wilayah::query()->delete();
        Wilayah::query()->insert($baris);

        $this->command?->info('Wilayah: '.count($baris).' desa/kelurahan di-seed.');
    }
}
