<?php

namespace Database\Seeders;

use App\Models\HargaSampah;
use App\Models\KategoriSampah;
use App\Models\KelompokSampah;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data awal Bank Sampah Indah Lestari, Kota Malang.
 * Fokus: sampah plastik. Harga di sini adalah harga PEMBUKA — admin
 * mengubahnya kapan saja lewat menu harga (harga plastik fluktuatif).
 */
class BankSampahSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@banksampah.test'],
            [
                'name' => 'Admin BSIL',
                'password' => Hash::make('rahasia123'),
                'role' => User::ROLE_ADMIN,
                'telepon' => null,
                'alamat' => 'Kota Malang',
                'aktif' => true,
            ]
        );

        // Grade plastik yang benar-benar dipisah saat penimbangan.
        // kelompok = kode kelompok kategori EPA ReCon (lihat KelompokSampahSeeder),
        // dipakai halaman harga untuk mengelompokkan jenis plastik.
        $this->call(KelompokSampahSeeder::class);

        // Faktor emisi konsolidasi EPA ReCon (kg CO2e/kg) per kategori —
        // rumus sederhana: E_A = W × EF; ekuivalensi pohon = kg CO2e / 60.
        // Tersedia (belum ada kategori): Aluminium (Kaleng) 10.061, Kaca 0.347.
        $kategori = [
            ['kode' => 'PET', 'nama' => 'Botol PET bening', 'keterangan' => 'Botol air mineral, bersih, label & tutup dilepas', 'harga' => 3000, 'kelompok' => 'PET', 'faktor' => 1.419],
            ['kode' => 'PET-W', 'nama' => 'Botol PET warna', 'keterangan' => 'Botol teh/soda berwarna', 'harga' => 2000, 'kelompok' => 'PET', 'faktor' => 1.419],
            ['kode' => 'PP', 'nama' => 'Gelas plastik (PP)', 'keterangan' => 'Gelas air mineral, sedotan dilepas', 'harga' => 2500, 'kelompok' => 'MIXED-PLASTICS', 'faktor' => 1.122],
            ['kode' => 'HDPE', 'nama' => 'Botol HDPE', 'keterangan' => 'Botol sampo, deterjen, jerigen', 'harga' => 2200, 'kelompok' => 'HDPE', 'faktor' => 1.099],
            ['kode' => 'KRESEK', 'nama' => 'Kresek & plastik campur', 'keterangan' => 'Kantong kresek, plastik bungkus bersih', 'harga' => 800, 'kelompok' => 'MIXED-PLASTICS', 'faktor' => 1.292],
        ];

        $kelompokIds = [];
        foreach ($kategori as $k) {
            $kelompokIds[$k['kelompok']] = KelompokSampah::where('kode', $k['kelompok'])->first()?->id;
        }

        foreach ($kategori as $k) {
            $model = KategoriSampah::updateOrCreate(
                ['kode' => $k['kode']],
                [
                    'nama' => $k['nama'],
                    'keterangan' => $k['keterangan'],
                    'kelompok_sampah_id' => $kelompokIds[$k['kelompok']] ?? null,
                    'faktor_emisi_kg_co2e' => $k['faktor'],
                    'aktif' => true,
                ]
            );

            if (! $model->hargaAktif()) {
                HargaSampah::create([
                    'kategori_sampah_id' => $model->id,
                    'harga_per_kg' => $k['harga'],
                    'berlaku_dari' => now()->toDateString(),
                    'berlaku_sampai' => null,
                    'dibuat_oleh' => $admin->id,
                ]);
            }
        }

        // Backfill faktor emisi dan harga awal untuk kategori tambahan
        // (PP00, CAMPUR, BESI). Jangan menimpa harga baris yang sudah ada.
        $tambahan = [
            'PP00' => ['faktor' => 1.122, 'nama' => 'PP gelas bening', 'harga' => 2000, 'kelompok' => 'MIXED-PLASTICS'],
            'CAMPUR' => ['faktor' => 1.292, 'nama' => 'Campur (aneka plastik)', 'harga' => 1500, 'kelompok' => 'MIXED-PLASTICS'],
            'BESI' => ['faktor' => 4.867, 'nama' => 'Besi & logam', 'harga' => 1000, 'kelompok' => null],
        ];

        foreach ($tambahan as $kode => $info) {
            $baris = KategoriSampah::where('kode', $kode)->first();

            if ($baris) {
                $baris->update(['faktor_emisi_kg_co2e' => $info['faktor']]);
            } else {
                $baris = KategoriSampah::create([
                    'kode' => $kode,
                    'nama' => $info['nama'],
                    'keterangan' => null,
                    'kelompok_sampah_id' => $info['kelompok'] ? ($kelompokIds[$info['kelompok']] ?? null) : null,
                    'faktor_emisi_kg_co2e' => $info['faktor'],
                    'aktif' => true,
                ]);
            }

            if (! $baris->hargaAktif()) {
                HargaSampah::create([
                    'kategori_sampah_id' => $baris->id,
                    'harga_per_kg' => $info['harga'],
                    'berlaku_dari' => now()->toDateString(),
                    'berlaku_sampai' => null,
                    'dibuat_oleh' => $admin->id,
                ]);
            }
        }
    }
}
