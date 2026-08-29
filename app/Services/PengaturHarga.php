<?php

namespace App\Services;

use App\Models\HargaSampah;
use App\Models\KategoriSampah;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PengaturHarga
{
    public function ubah(
        KategoriSampah $kategori,
        int $hargaBaruPerKg,
        User $admin,
        ?string $berlakuDari = null,
    ): HargaSampah {
        if ($hargaBaruPerKg <= 0) {
            throw new RuntimeException('Harga harus lebih dari 0.');
        }

        $mulai = $berlakuDari ? Carbon::parse($berlakuDari) : Carbon::today();

        return DB::transaction(function () use ($kategori, $hargaBaruPerKg, $admin, $mulai) {
            $hargaLama = $kategori->hargaAktif();

            if ($hargaLama) {
                $hargaLama->update([
                    'berlaku_sampai' => $mulai->copy()->subDay()->toDateString(),
                ]);
            }

            $baru = HargaSampah::create([
                'kategori_sampah_id' => $kategori->id,
                'harga_per_kg' => $hargaBaruPerKg,
                'berlaku_dari' => $mulai->toDateString(),
                'berlaku_sampai' => null,
                'dibuat_oleh' => $admin->id,
            ]);

            // Harga berubah → cache harga aktif kategori ini basi.
            KategoriSampah::lupakanHargaAktif($kategori->id);

            return $baru;
        });
    }

    /**
     * Pastikan setiap kategori aktif punya setidaknya satu harga.
     * Kalau belum, buat dengan harga default.
     *
     * Optimasi: 1 query untuk cari kategori tanpa harga, bukan N+1.
     */
    public function inisialisasiKategoriTampaHarga(User $admin): int
    {
        // Ambil id kategori yang tidak punya harga aktif (berlaku_sampai IS NULL)
        $idsTanpaHarga = KategoriSampah::where('aktif', true)
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)
                    ->from('harga_sampah')
                    ->whereColumn('harga_sampah.kategori_sampah_id', 'kategori_sampah.id')
                    ->whereNull('berlaku_sampai');
            })
            ->pluck('id');

        if ($idsTanpaHarga->isEmpty()) {
            return 0;
        }

        $count = 0;
        $kategoris = KategoriSampah::whereIn('id', $idsTanpaHarga)->get();
        foreach ($kategoris as $k) {
            $this->ubah(
                kategori: $k,
                hargaBaruPerKg: 1000,
                admin: $admin,
            );
            $count++;
        }

        return $count;
    }
}
