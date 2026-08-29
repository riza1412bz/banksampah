<?php

namespace App\Services;

use App\Models\KategoriSampah;

/**
 * Kalkulator dampak lingkungan — rumus konsolidasi EPA ReCon (sederhana).
 *
 * FORMULA
 * - E_A = W × EF, dengan EF = faktor emisi konsolidasi per kategori
 *   (kg CO2e/kg) yang tersimpan di kolom kategori_sampah.faktor_emisi_kg_co2e.
 * - Ekuivalensi bibit pohon = E_A / 60 (1 pohon ≈ 60 kg CO2e selama 10 tahun).
 *
 * SATUAN
 * - Masukan berat dalam kg, keluaran kg CO2e.
 * - Tidak lagi memakai metodologi baseline/alternate ReCon kompleks maupun
 *   hitungan energi (MJ) — cukup faktor konsolidasi per kategori.
 */
class PerhitunganDampak
{
    /** kg CO2e yang diserap satu bibit pohon selama 10 tahun. */
    public const KG_CO2E_PER_POHON = 60.0;

    /**
     * Dampak per kilogram untuk satu kategori sampah.
     *
     * @return array{ghg_kg_co2e: float, kelompok_kode: string|null, kelompok_nama: string|null}
     */
    public function perKategori(KategoriSampah $kategori): array
    {
        return [
            'ghg_kg_co2e' => (float) ($kategori->faktor_emisi_kg_co2e ?? 0.0),
            'kelompok_kode' => $kategori->kelompok?->kode,
            'kelompok_nama' => $kategori->kelompok?->nama,
        ];
    }

    /**
     * Ekuivalensi bibit pohon untuk sekian kg CO2e (1 pohon ≈ 60 kg CO2e).
     */
    public function ekuivalensiPohon(float $kgCo2e): float
    {
        return $kgCo2e / self::KG_CO2E_PER_POHON;
    }

    /**
     * Dampak total untuk sekumpulan item setoran (per kg tiap kategori).
     *
     * @param  iterable<KategoriSampah>  $kategori  tiap item
     * @return array{ghg_kg_co2e: float, per_kelompok: array<string, array{ghg_kg_co2e: float}>}
     */
    public function untukItems(iterable $kategori): array
    {
        $totalGhg = 0.0;
        $perKelompok = [];

        foreach ($kategori as $k) {
            $dampak = $this->perKategori($k);
            $nama = $dampak['kelompok_nama'] ?? 'Tanpa kelompok';
            $perKelompok[$nama]['ghg_kg_co2e'] = ($perKelompok[$nama]['ghg_kg_co2e'] ?? 0) + $dampak['ghg_kg_co2e'];
            $totalGhg += $dampak['ghg_kg_co2e'];
        }

        return [
            'ghg_kg_co2e' => $totalGhg,
            'per_kelompok' => $perKelompok,
        ];
    }
}
