<?php

namespace App\Services;

use App\Models\KategoriSampah;

/**
 * Kalkulator dampak lingkungan & emisi terhindar — EPA WARM v16 & EPR Analytics.
 *
 * FORMULA
 * - E_terhindar = Σ (berat × EF), dengan EF = faktor emisi konsolidasi per kategori
 *   (kg CO2e/kg) yang tersimpan di kolom kategori_sampah.faktor_emisi_kg_co2e.
 * - Ekuivalensi bibit pohon = E_terhindar / 22.9 (1 bibit pohon perkotaan ≈ 22.9 kg CO2e / 10 tahun).
 * - Ekuivalensi mobil bensin = E_terhindar × 4.0029 (jarak tempuh km perjalanan mobil bensin dipangkas).
 * - Ekuivalensi lampu LED 10W = (E_terhindar / 0.85) × 100 (jam pemakaian lampu LED 10W dihemat).
 */
class PerhitunganDampak
{
    /** kg CO2e yang diserap satu bibit pohon selama 10 tahun (EPA GHG Equivalencies). */
    public const KG_CO2E_PER_POHON = 22.9;

    /** Jarak tempuh km perjalanan mobil bensin per kg CO2e. */
    public const KM_PER_KG_CO2E_MOBIL = 4.0029;

    /** Faktor emisi grid listrik Indonesia (kg CO2e / kWh). */
    public const KG_CO2E_PER_KWH = 0.85;

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
     * Ekuivalensi bibit pohon untuk sekian kg CO2e (1 pohon ≈ 22.9 kg CO2e / 10 tahun).
     */
    public function ekuivalensiPohon(float $kgCo2e): float
    {
        return $kgCo2e / self::KG_CO2E_PER_POHON;
    }

    /**
     * Ekuivalensi kilometer perjalanan mobil bensin dipangkas.
     */
    public function ekuivalensiMobil(float $kgCo2e): float
    {
        return $kgCo2e * self::KM_PER_KG_CO2E_MOBIL;
    }

    /**
     * Ekuivalensi jam penghematan pemakaian lampu LED 10W.
     */
    public function ekuivalensiLampuLed(float $kgCo2e): float
    {
        return ($kgCo2e / self::KG_CO2E_PER_KWH) * 100;
    }

    /**
     * Dampak total untuk sekumpulan item setoran (per kg tiap kategori).
     *
     * @param  iterable<KategoriSampah>  $kategori  tiap item
     * @return array{ghg_kg_co2e: float, pohon: float, mobil_km: float, lampu_led_jam: float, per_kelompok: array<string, array{ghg_kg_co2e: float}>}
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
            'pohon' => $this->ekuivalensiPohon($totalGhg),
            'mobil_km' => $this->ekuivalensiMobil($totalGhg),
            'lampu_led_jam' => $this->ekuivalensiLampuLed($totalGhg),
            'per_kelompok' => $perKelompok,
        ];
    }
}

