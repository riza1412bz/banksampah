<?php

namespace App\Services;

use App\Models\Setoran;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service untuk sinkronisasi pencatatan transaksi setoran ke Google Sheets
 * via Google Apps Script Webhook (POST JSON).
 */
class GoogleSheetsSync
{
    /**
     * Cek apakah webhook Google Sheets sudah dikonfigurasi dan aktif.
     */
    public function isConfigured(): bool
    {
        $enabled = (bool) config('services.google_sheets.enabled', true);
        $url = config('services.google_sheets.webhook_url');

        return $enabled && ! empty($url) && filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Dapatkan URL Webhook Google Sheets.
     */
    public function getWebhookUrl(): ?string
    {
        return config('services.google_sheets.webhook_url');
    }

    /**
     * Format 1 baris model Setoran menjadi array data terstruktur.
     */
    public function formatSetoran(Setoran $setoran): array
    {
        // Pastikan relasi ter-load
        if (! $setoran->relationLoaded('user')) {
            $setoran->load('user');
        }
        if (! $setoran->relationLoaded('kategori')) {
            $setoran->load('kategori');
        }
        if (! $setoran->relationLoaded('dicatatOleh')) {
            $setoran->load('dicatatOleh');
        }

        $nasabah = $setoran->user;
        $kategori = $setoran->kategori;
        $petugas = $setoran->dicatatOleh;

        $beratKg = round($setoran->berat_gram / 1000, 3);
        $faktorEmisi = (float) ($kategori?->faktor_emisi_kg_co2e ?? 0.0);
        $emisiTerhindar = round($beratKg * $faktorEmisi, 4);

        $jenisNasabah = 'Perorangan';
        if ($nasabah?->isCorporate()) {
            $jenisNasabah = 'Corporate / Mitra';
        }

        return [
            'id' => $setoran->id,
            'waktu_input' => $setoran->created_at ? $setoran->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            'nomor_bukti' => $setoran->nomor_bukti,
            'tanggal_setor' => $setoran->tanggal_setor ? (string) $setoran->tanggal_setor : now()->toDateString(),
            'kode_nasabah' => $nasabah?->kode_nasabah ?? '-',
            'nama_nasabah' => $nasabah?->name ?? 'Nasabah Anonim',
            'jenis_nasabah' => $jenisNasabah,
            'kategori_sampah' => $kategori?->nama ?? 'Sampah Umum',
            'kode_kategori' => $kategori?->kode ?? '-',
            'berat_kg' => $beratKg,
            'harga_per_kg' => (int) $setoran->harga_per_kg,
            'total_rupiah' => (int) $setoran->total_rupiah,
            'faktor_emisi' => $faktorEmisi,
            'emisi_terhindar_kg_co2e' => $emisiTerhindar,
            'petugas' => $petugas?->name ?? 'Admin',
            'catatan' => $setoran->catatan ?? '',
        ];
    }

    /**
     * Kirim data batch setoran ke Google Sheets Webhook.
     *
     * @param  iterable<Setoran>|Setoran  $setoran
     */
    public function sync(iterable|Setoran $setoran): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $items = [];
        if ($setoran instanceof Setoran) {
            $items[] = $this->formatSetoran($setoran);
        } else {
            foreach ($setoran as $s) {
                if ($s instanceof Setoran) {
                    $items[] = $this->formatSetoran($s);
                }
            }
        }

        if (empty($items)) {
            return false;
        }

        $payload = [
            'source' => 'banksampah-app',
            'timestamp' => now()->toIso8601String(),
            'count' => count($items),
            'items' => $items,
        ];

        return $this->sendPayload($payload);
    }

    /**
     * Kirim payload JSON ke URL Webhook Google Apps Script.
     */
    public function sendPayload(array $payload): bool
    {
        $url = $this->getWebhookUrl();
        if (! $url) {
            return false;
        }

        // Sertakan shared secret bila dikonfigurasi, supaya Apps Script bisa menolak pengirim asing.
        $secret = config('services.google_sheets.secret');
        if ($secret) {
            $payload['secret'] = $secret;
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('GoogleSheetsSync: Berhasil mengirim ' . ($payload['count'] ?? 1) . ' baris ke Google Sheets.');

                return true;
            }

            Log::warning('GoogleSheetsSync: Webhook merespons status HTTP ' . $response->status(), [
                'body' => $response->body(),
            ]);

            return false;
        } catch (Throwable $e) {
            // Fail-safe: Tidak pernah melempar exception ke alur utama
            Log::error('GoogleSheetsSync: Gagal menghubungi Google Sheets Webhook: ' . $e->getMessage());

            return false;
        }
    }
}
