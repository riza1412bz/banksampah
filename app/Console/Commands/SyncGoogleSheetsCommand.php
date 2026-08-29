<?php

namespace App\Console\Commands;

use App\Models\Setoran;
use App\Services\GoogleSheetsSync;
use Illuminate\Console\Command;

class SyncGoogleSheetsCommand extends Command
{
    protected $signature = 'sheets:sync {--test : Kirim data pengujian untuk memvalidasi koneksi webhook} {--all : Sinkronkan seluruh data setoran yang ada di database}';

    protected $description = 'Uji atau sinkronkan data transaksi setoran ke Google Sheets Webhook';

    public function handle(GoogleSheetsSync $sync): int
    {
        if (! $sync->isConfigured()) {
            $this->warn('Webhook Google Sheets belum dikonfigurasi di file .env.');
            $this->line('Tambahkan baris berikut di .env:');
            $this->info('GOOGLE_SHEETS_WEBHOOK_URL=https://script.google.com/macros/s/XXXXX/exec');

            return self::FAILURE;
        }

        $this->info('URL Webhook: ' . $sync->getWebhookUrl());

        if ($this->option('test')) {
            $this->info('Mengirim data uji coba...');

            $testPayload = [
                'source' => 'banksampah-app',
                'timestamp' => now()->toIso8601String(),
                'count' => 1,
                'items' => [
                    [
                        'id' => 0,
                        'waktu_input' => now()->format('Y-m-d H:i:s'),
                        'nomor_bukti' => 'TEST-001',
                        'tanggal_setor' => now()->toDateString(),
                        'kode_nasabah' => 'BSIL-TEST',
                        'nama_nasabah' => 'Nasabah Uji Coba',
                        'jenis_nasabah' => 'Perorangan',
                        'kategori_sampah' => 'Botol PET Bening',
                        'kode_kategori' => 'P14',
                        'berat_kg' => 2.5,
                        'harga_per_kg' => 3000,
                        'total_rupiah' => 7500,
                        'faktor_emisi' => 1.8,
                        'emisi_terhindar_kg_co2e' => 4.5,
                        'petugas' => 'Admin Sistem',
                        'catatan' => 'Baris pengujian integrasi Google Sheets',
                    ],
                ],
            ];

            $success = $sync->sendPayload($testPayload);

            if ($success) {
                $this->info('✅ Berhasil terhubung ke Google Sheets! Data uji coba berhasil ditambahkan.');

                return self::SUCCESS;
            } else {
                $this->error('❌ Gagal mengirim data ke Google Sheets. Periksa URL Webhook atau izin deployment Apps Script.');

                return self::FAILURE;
            }
        }

        if ($this->option('all')) {
            $total = Setoran::count();
            if ($total === 0) {
                $this->info('Tidak ada data setoran di database untuk disinkronkan.');

                return self::SUCCESS;
            }

            $this->info("Menemukan {$total} transaksi setoran. Memulai pengiriman batch...");

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            Setoran::with(['nasabah', 'kategori', 'petugas'])
                ->orderBy('id')
                ->chunk(50, function ($chunk) use ($sync, $bar) {
                    $sync->sync($chunk);
                    $bar->advance($chunk->count());
                });

            $bar->finish();
            $this->newLine();
            $this->info("✅ Seluruh {$total} transaksi setoran berhasil disinkronkan ke Google Sheets!");

            return self::SUCCESS;
        }

        $this->comment('Gunakan opsi:');
        $this->line('  php artisan sheets:sync --test  (Uji coba koneksi)');
        $this->line('  php artisan sheets:sync --all   (Kirim semua data yang ada)');

        return self::SUCCESS;
    }
}
