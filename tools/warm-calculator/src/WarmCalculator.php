<?php

namespace Tools\WarmCalculator;

/**
 * Kalkulator Emisi Terhindar & Dampak Lingkungan
 * Berdasarkan Metodologi EPA WARM v16 (Waste Reduction Model).
 */
class WarmCalculator
{
    protected array $factorsData;

    public function __construct(?string $factorsJsonPath = null)
    {
        $path = $factorsJsonPath ?? __DIR__ . '/../data/warm_v16_factors.json';
        if (!file_exists($path)) {
            throw new \RuntimeException("Faktor emisi WARM tidak ditemukan di: {$path}");
        }

        $content = file_get_contents($path);
        $this->factorsData = json_decode($content, true) ?: [];
    }

    /**
     * Dapatkan daftar seluruh material yang didukung.
     */
    public function getMaterials(): array
    {
        return $this->factorsData['materials'] ?? [];
    }

    /**
     * Cari material berdasarkan kode atau nama WARM.
     */
    public function findMaterial(string $identifier): ?array
    {
        $idLower = strtolower(trim($identifier));
        foreach ($this->getMaterials() as $mat) {
            if (
                strtolower($mat['kode']) === $idLower ||
                strtolower($mat['warm_name']) === $idLower ||
                strtolower($mat['nama_id']) === $idLower
            ) {
                return $mat;
            }
        }
        return null;
    }

    /**
     * Hitung laporan emisi terhindar dan ekuivalensi untuk sekumpulan item setoran.
     *
     * @param array<int, array{kode?: string, warm_name?: string, nama_kategori?: string, volume_kg: float, custom_ef?: float}> $items
     * @param array{mitra?: string, no_manifes?: string, periode?: string, fasilitas?: string} $metadata
     * @return array
     */
    public function calculateReport(array $items, array $metadata = []): array
    {
        $breakdown = [];
        $totalVolumeKg = 0.0;
        $totalEmisiTerhindar = 0.0;
        $totalEnergySavingsKwh = 0.0;

        foreach ($items as $item) {
            $volume = (float) ($item['volume_kg'] ?? 0.0);
            if ($volume <= 0) {
                continue;
            }

            $mat = null;
            if (!empty($item['kode'])) {
                $mat = $this->findMaterial($item['kode']);
            } elseif (!empty($item['warm_name'])) {
                $mat = $this->findMaterial($item['warm_name']);
            }

            // Gunakan custom EF jika diberikan, atau ambil dari database faktor
            $ef = 0.0;
            if (isset($item['custom_ef']) && is_numeric($item['custom_ef'])) {
                $ef = (float) $item['custom_ef'];
            } elseif ($mat) {
                $ef = (float) ($mat['ef_warm_net_kg_co2e'] ?? 0.0);
            }

            $emisiTerhindar = $volume * $ef;
            $energyKwhPerKg = (float) ($mat['energy_savings_kwh_per_kg'] ?? 0.0);
            $energySavings = $volume * $energyKwhPerKg;

            $totalVolumeKg += $volume;
            $totalEmisiTerhindar += $emisiTerhindar;
            $totalEnergySavingsKwh += $energySavings;

            $breakdown[] = [
                'kode' => $item['kode'] ?? ($mat['kode'] ?? '-'),
                'kategori' => $item['nama_kategori'] ?? ($mat['nama_id'] ?? ($mat['warm_name'] ?? 'Material Anorganik')),
                'volume_kg' => round($volume, 2),
                'faktor_emisi' => round($ef, 2),
                'emisi_terhindar_kg_co2e' => round($emisiTerhindar, 2),
                'energy_savings_kwh' => round($energySavings, 2),
            ];
        }

        // Hitung Ekuivalensi Publik (Relatable Metrics)
        $pohon = (int) round($totalEmisiTerhindar / 22.9);
        $kmMobil = (int) round($totalEmisiTerhindar * 4.0029);

        // Jam lampu LED 10W: jika total energi dihitung dari WARM atau dari faktor emisi avoided
        // 1 kWh = 100 jam lampu 10W (10W = 0.01 kW).
        $jamLampuLed = 0;
        if ($totalEnergySavingsKwh > 0) {
            $jamLampuLed = (int) round($totalEnergySavingsKwh / 0.010);
        } else {
            // Estimasi ekuivalen dari kg CO2e grid Indonesia (1 kWh ≈ 0.85 kg CO2e)
            $jamLampuLed = (int) round(($totalEmisiTerhindar / 0.85) / 0.010);
        }

        $jamLampuFormat = $jamLampuLed >= 1000 ? round($jamLampuLed / 1000, 0) . 'K Jam' : number_format($jamLampuLed) . ' Jam';

        return [
            'metadata' => [
                'mitra_korporasi' => $metadata['mitra'] ?? '—',
                'no_manifes' => $metadata['no_manifes'] ?? ('001/PNB/BSIL/' . date('m/Y')),
                'periode_analisis' => $metadata['periode'] ?? date('F Y'),
                'fasilitas_pengelola' => $metadata['fasilitas'] ?? 'Bank Sampah Indah Lestari',
            ],
            'ringkasan' => [
                'total_volume_kg' => round($totalVolumeKg, 2),
                'total_emisi_terhindar_kg_co2e' => round($totalEmisiTerhindar, 2),
                'total_energy_savings_kwh' => round($totalEnergySavingsKwh, 2),
            ],
            'ekuivalensi' => [
                'pohon' => [
                    'nilai' => $pohon,
                    'satuan' => 'pohon',
                    'label' => 'Bibit pohon yang tumbuh subur selama 10 tahun penuh',
                    'deskripsi' => 'Setara dengan serapan karbon tanaman penghijauan perkotaan selama 1 dekade.',
                ],
                'mobil_km' => [
                    'nilai' => $kmMobil,
                    'satuan' => 'KM',
                    'label' => 'Memotong emisi gas buang perjalanan mobil bensin',
                    'deskripsi' => 'Pengurangan polusi knalpot kendaraan berbahan bakar bensin rata-rata.',
                ],
                'lampu_led_jam' => [
                    'nilai' => $jamLampuLed,
                    'format' => $jamLampuFormat,
                    'satuan' => 'Jam',
                    'label' => 'Penghematan konsumsi daya lampu LED hemat energi 10W',
                    'deskripsi' => 'Daya listrik yang berhasil dihemat untuk operasional pencahayaan.',
                ],
            ],
            'breakdown' => $breakdown,
        ];
    }
}
