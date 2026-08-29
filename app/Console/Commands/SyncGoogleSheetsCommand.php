<?php

namespace App\Console\Commands;

use App\Models\KategoriSampah;
use App\Models\Setoran;
use App\Models\User;
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

            // Bangun baris uji lewat formatSetoran() agar 15 kolomnya tak pernah drift dari kode asli.
            $contoh = new Setoran([
                'nomor_bukti' => 'TEST-001',
                'berat_gram' => 2500,
                'harga_per_kg' => 3000,
                'total_rupiah' => 7500,
                'tanggal_setor' => now()->toDateString(),
                'catatan' => 'Baris pengujian integrasi Google Sheets',
            ]);
            $contoh->id = 0;
            $contoh->setRelation('user', new User(['name' => 'Nasabah Uji Coba', 'kode_nasabah' => 'BSIL-TEST', 'jenis_nasabah' => User::JENIS_PERORANGAN]));
            $contoh->setRelation('kategori', new KategoriSampah(['nama' => 'Botol PET Bening', 'kode' => 'P14', 'faktor_emisi_kg_co2e' => 1.8]));
            $contoh->setRelation('dicatatOleh', new User(['name' => 'Admin Sistem']));

            $testPayload = [
                'source' => 'banksampah-app',
                'timestamp' => now()->toIso8601String(),
                'count' => 1,
                'items' => [$sync->formatSetoran($contoh)],
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

            Setoran::with(['user', 'kategori', 'dicatatOleh'])
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
