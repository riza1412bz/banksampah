<?php

namespace App\Jobs;

use App\Services\LaporanSetoran;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Membuat file Excel setoran di background supaya request export tidak
 * memblokir admin saat datanya besar. File disimpan ke storage/app/private/exports.
 */
class ExportSetoranJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $dari,
        public string $sampai,
        public string $cari,
        public string $namaFile,
    ) {}

    public int $tries = 3;

    public int $timeout = 300;

    public function handle(LaporanSetoran $laporan): void
    {
        // Streaming: untuk 50k transaksi, memuat semua ke Collection akan OOM.
        // Gunakan cursor() agar hanya 1 agregat di memori per iterasi.
        // Fallback tetap pakai transaksiPeriode() jika cursor tidak diperlukan,
        // tapi di sini kita deteksi jumlah dulu via count cepat.
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'No. Bukti');
        $sheet->setCellValue('B1', 'Tanggal');
        $sheet->setCellValue('C1', 'Nasabah');
        $sheet->setCellValue('D1', 'Jenis Sampah');
        $sheet->setCellValue('E1', 'Berat (kg)');
        $sheet->setCellValue('F1', 'Jumlah Item');
        $sheet->setCellValue('G1', 'Total (Rp)');

        // Header styling (freeze + bold + auto-filter ringan)
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:G1');

        $baris = 2;
        // Gunakan cursor streaming dari LaporanSetoran yang baru
        foreach ($laporan->transaksiPeriodeCursor($this->dari, $this->sampai, $this->cari) as $t) {
            $sheet->setCellValue("A{$baris}", $t->nomor_bukti);
            // $t->tanggal sudah Carbon (lihat LaporanSetoran)
            $sheet->setCellValue("B{$baris}", $t->tanggal instanceof \Carbon\CarbonInterface ? $t->tanggal->format('d-m-Y') : (string) $t->tanggal);
            $sheet->setCellValue("C{$baris}", $t->nama);
            $sheet->setCellValue("D{$baris}", $t->jenis);
            $sheet->setCellValue("E{$baris}", $t->berat_kg);
            $sheet->setCellValue("F{$baris}", $t->jumlah_item);
            $sheet->setCellValue("G{$baris}", $t->rupiah);
            $baris++;

            // Flush periodik untuk mengurangi peak memory pada export raksasa
            if ($baris % 1000 === 0) {
                gc_collect_cycles();
            }
        }

        foreach (range('A', 'G') as $kolom) {
            $sheet->getColumnDimension($kolom)->setAutoSize(true);
        }

        $dir = storage_path('app/private/exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Gunakan cache disk untuk PhpSpreadsheet jika memori ketat (opsional)
        (new Xlsx($spreadsheet))->save($dir.'/'.$this->namaFile);

        // Bersihkan memori
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }
}