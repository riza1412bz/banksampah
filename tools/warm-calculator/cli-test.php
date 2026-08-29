<?php

require_once __DIR__ . '/src/WarmCalculator.php';

use Tools\WarmCalculator\WarmCalculator;

echo "========================================================================\n";
echo "  TEST KALKULATOR WARM v16 & VERIFIKASI DOKUMEN WASTE REPORT.pdf\n";
echo "========================================================================\n\n";

$calculator = new WarmCalculator();

// TEST CASE 1: Persis seperti data di WASTE REPORT.pdf
$samplePdfItems = [
    [
        'kode' => 'K9',
        'nama_kategori' => 'Kertas / Dokumen Perkantoran Terpilah',
        'volume_kg' => 37.7,
        'custom_ef' => 1.20,
    ],
    [
        'kode' => 'P14',
        'nama_kategori' => 'Plastik Rigi PET (Botol Bening Pasca- Konsumsi)',
        'volume_kg' => 51.2,
        'custom_ef' => 1.80,
    ],
];

$metadataPdf = [
    'mitra' => '—',
    'no_manifes' => '001/PNB/BSIL/VII/2026',
    'periode' => 'Juli 2026',
    'fasilitas' => 'Bank Sampah Indah Lestari',
];

$result1 = $calculator->calculateReport($samplePdfItems, $metadataPdf);

echo "TEST CASE 1 (Kesesuaian dengan WASTE REPORT.pdf):\n";
echo "------------------------------------------------------------------------\n";
echo "Mitra Korporasi    : " . $result1['metadata']['mitra_korporasi'] . "\n";
echo "No. Manifes / Nota : " . $result1['metadata']['no_manifes'] . "\n";
echo "Periode Analisis   : " . $result1['metadata']['periode_analisis'] . "\n";
echo "Fasilitas Pengelola: " . $result1['metadata']['fasilitas_pengelola'] . "\n\n";

echo "TABEL DISTRIBUSI KOMODITAS:\n";
printf("%-6s | %-45s | %-10s | %-12s | %-16s\n", "KODE", "KATEGORI MATERIAL", "VOL (KG)", "EF (kg/kg)", "EMISI TERHINDAR");
echo str_repeat("-", 100) . "\n";

foreach ($result1['breakdown'] as $row) {
    printf(
        "%-6s | %-45s | %10.1f | %12.2f | %13.2f kg CO2e\n",
        $row['kode'],
        $row['kategori'],
        $row['volume_kg'],
        $row['faktor_emisi'],
        $row['emisi_terhindar_kg_co2e']
    );
}
echo str_repeat("-", 100) . "\n";
printf(
    "%-6s | %-45s | %10.1f | %12s | %13.2f kg CO2e\n",
    "TOTAL",
    "Akumulasi Pengalihan Material dari TPA",
    $result1['ringkasan']['total_volume_kg'],
    "-",
    $result1['ringkasan']['total_emisi_terhindar_kg_co2e']
);

echo "\nEKUIVALENSI DAMPAK LINGKUNGAN (RELATABLE METRICS):\n";
echo "1. Bibit Pohon : " . $result1['ekuivalensi']['pohon']['nilai'] . " " . $result1['ekuivalensi']['pohon']['satuan'] . " (" . $result1['ekuivalensi']['pohon']['label'] . ")\n";
echo "2. Mobil Bensin: " . $result1['ekuivalensi']['mobil_km']['nilai'] . " " . $result1['ekuivalensi']['mobil_km']['satuan'] . " (" . $result1['ekuivalensi']['mobil_km']['label'] . ")\n";
echo "3. Lampu LED   : " . $result1['ekuivalensi']['lampu_led_jam']['format'] . " (" . $result1['ekuivalensi']['lampu_led_jam']['label'] . ")\n";

// Assertions check
$expectedVolume = 88.9;
$expectedEmisi = 137.40;
$expectedPohon = 6;
$expectedKm = 550;

$volPass = abs($result1['ringkasan']['total_volume_kg'] - $expectedVolume) < 0.01;
$emisiPass = abs($result1['ringkasan']['total_emisi_terhindar_kg_co2e'] - $expectedEmisi) < 0.01;
$pohonPass = $result1['ekuivalensi']['pohon']['nilai'] === $expectedPohon;
$kmPass = $result1['ekuivalensi']['mobil_km']['nilai'] === $expectedKm;

echo "\nSTATUS VERIFIKASI TEST CASE 1:\n";
echo " - Total Volume (88.9 KG)      : " . ($volPass ? "✅ PASSED" : "❌ FAILED") . "\n";
echo " - Total Emisi (137.40 kg CO2e): " . ($emisiPass ? "✅ PASSED" : "❌ FAILED") . "\n";
echo " - Pohon (6 Pohon)             : " . ($pohonPass ? "✅ PASSED" : "❌ FAILED") . "\n";
echo " - Mobil (550 KM)              : " . ($kmPass ? "✅ PASSED" : "❌ FAILED") . "\n";

echo "\n========================================================================\n";
echo "TEST CASE 2: Setoran Aneka Sampah Campuran (Menggunakan Faktor WARM v16 Murni)\n";
echo "========================================================================\n";

$campuranItems = [
    ['warm_name' => 'Aluminum Cans', 'volume_kg' => 15.0],
    ['warm_name' => 'PET', 'volume_kg' => 25.0],
    ['warm_name' => 'Corrugated Containers', 'volume_kg' => 40.0],
    ['warm_name' => 'Glass', 'volume_kg' => 30.0],
    ['warm_name' => 'Food Waste', 'volume_kg' => 50.0],
];

$result2 = $calculator->calculateReport($campuranItems, [
    'mitra' => 'Komunitas Warga RW 05',
    'no_manifes' => '002/PNB/BSIL/VIII/2026',
    'periode' => 'Agustus 2026',
]);

echo "Total Sampah Terkelola  : " . $result2['ringkasan']['total_volume_kg'] . " KG\n";
echo "Total Emisi Terhindar   : " . $result2['ringkasan']['total_emisi_terhindar_kg_co2e'] . " kg CO2e\n";
echo "Total Energi Dihemat    : " . $result2['ringkasan']['total_energy_savings_kwh'] . " kWh\n";
echo "Setara Bibit Pohon      : " . $result2['ekuivalensi']['pohon']['nilai'] . " pohon\n";
echo "Setara Jarak Mobil      : " . $result2['ekuivalensi']['mobil_km']['nilai'] . " KM perjalanan mobil bensin\n";
echo "Setara Lampu LED        : " . $result2['ekuivalensi']['lampu_led_jam']['format'] . "\n\n";

echo "Semua pengujian kalkulator selesai dijalankan!\n";
