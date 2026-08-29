<?php

namespace Tests\Feature;

use App\Jobs\SyncSetoranToGoogleSheets;
use App\Models\HargaSampah;
use App\Models\KategoriSampah;
use App\Models\KelompokSampah;
use App\Models\Setoran;
use App\Models\User;
use App\Services\GoogleSheetsSync;
use App\Services\PencatatSetoran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GoogleSheetsSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $nasabah;
    private KategoriSampah $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin Pengurus',
        ]);

        $this->nasabah = User::factory()->create([
            'role' => User::ROLE_NASABAH,
            'name' => 'Pak Budi',
            'kode_nasabah' => 'BSIL-0123',
        ]);

        $kelompok = KelompokSampah::create([
            'kode' => 'P14',
            'nama' => 'Plastik PET',
            'deskripsi' => 'Plastik & Polimer — PET',
            'urutan' => 1,
            'default_recycled_content' => 0,
            'ef_virgin' => 0,
            'ef_recycled' => 1.80,
            'ef_current' => 0,
            'forest_c_seq' => 0,
            'energy_virgin' => 0,
            'energy_recycled' => 0,
            'energy_current' => 0,
            'landfilling_ef' => 0.02,
            'combustion_ef' => 0,
            'loss_rate' => 1.0,
            'aktif' => true,
        ]);

        $this->kategori = KategoriSampah::create([
            'kode' => 'PET',
            'nama' => 'Botol PET Bening',
            'kelompok_sampah_id' => $kelompok->id,
            'faktor_emisi_kg_co2e' => 1.80,
            'aktif' => true,
        ]);

        HargaSampah::create([
            'kategori_sampah_id' => $this->kategori->id,
            'harga_per_kg' => 3000,
            'berlaku_dari' => now()->toDateString(),
            'dibuat_oleh' => $this->admin->id,
        ]);
    }

    public function test_format_setoran_menghasilkan_data_terstruktur_dan_emisi_yang_tepat(): void
    {
        $setoran = app(PencatatSetoran::class)->catat(
            nasabah: $this->nasabah,
            kategori: $this->kategori,
            beratGram: 2500, // 2.5 kg
            petugas: $this->admin,
            catatan: 'Botol bersih tanpa label',
        );

        $sync = app(GoogleSheetsSync::class);
        $data = $sync->formatSetoran($setoran);

        $this->assertSame($setoran->nomor_bukti, $data['nomor_bukti']);
        $this->assertSame('BSIL-0123', $data['kode_nasabah']);
        $this->assertSame('Pak Budi', $data['nama_nasabah']);
        $this->assertSame('Perorangan', $data['jenis_nasabah']);
        $this->assertSame('Botol PET Bening', $data['kategori_sampah']);
        $this->assertSame(2.5, $data['berat_kg']);
        $this->assertSame(3000, $data['harga_per_kg']);
        $this->assertSame(7500, $data['total_rupiah']);
        $this->assertSame(1.80, $data['faktor_emisi']);
        $this->assertSame(4.5, $data['emisi_terhindar_kg_co2e']); // 2.5 * 1.80 = 4.5
        $this->assertSame('Admin Pengurus', $data['petugas']);
        $this->assertSame('Botol bersih tanpa label', $data['catatan']);
    }

    public function test_sync_mengirimkan_payload_ke_webhook_url(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_TEST/exec';
        Config::set('services.google_sheets.webhook_url', $webhookUrl);
        Config::set('services.google_sheets.enabled', true);

        Http::fake([
            $webhookUrl => Http::response(['status' => 'success'], 200),
        ]);

        $setoran = app(PencatatSetoran::class)->catat(
            nasabah: $this->nasabah,
            kategori: $this->kategori,
            beratGram: 1000,
            petugas: $this->admin,
        );

        $sync = app(GoogleSheetsSync::class);
        $result = $sync->sync($setoran);

        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($webhookUrl) {
            return $request->url() === $webhookUrl &&
                $request['source'] === 'banksampah-app' &&
                count($request['items']) === 1 &&
                $request['items'][0]['kode_nasabah'] === 'BSIL-0123';
        });
    }

    public function test_pencatatan_setoran_men_dispatch_job_ke_antrean(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)->post(route('admin.setoran.store'), [
            'user_id' => $this->nasabah->id,
            'items' => [
                $this->kategori->id => [
                    'checked' => '1',
                    'berat_kg' => '3.5',
                ],
            ],
        ])->assertRedirect();

        Queue::assertPushed(SyncSetoranToGoogleSheets::class, function ($job) {
            return count($job->setoranIds) === 1;
        });
    }

    public function test_command_sheets_sync_test_berhasil(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_TEST/exec';
        Config::set('services.google_sheets.webhook_url', $webhookUrl);
        Config::set('services.google_sheets.enabled', true);

        Http::fake([
            $webhookUrl => Http::response(['status' => 'success'], 200),
        ]);

        $this->artisan('sheets:sync', ['--test' => true])
            ->expectsOutputToContain('Berhasil terhubung ke Google Sheets')
            ->assertSuccessful();
    }

    public function test_command_sheets_sync_all_mengirimkan_data_database(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_TEST/exec';
        Config::set('services.google_sheets.webhook_url', $webhookUrl);
        Config::set('services.google_sheets.enabled', true);

        Http::fake([
            $webhookUrl => Http::response(['status' => 'success'], 200),
        ]);

        app(PencatatSetoran::class)->catat(
            nasabah: $this->nasabah,
            kategori: $this->kategori,
            beratGram: 1000,
            petugas: $this->admin,
        );

        $this->artisan('sheets:sync', ['--all' => true])
            ->expectsOutputToContain('transaksi setoran berhasil disinkronkan')
            ->assertSuccessful();
    }

    public function test_sync_gagal_atau_timeout_tidak_merusak_aplikasi_karena_failsafe(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_TEST/exec';
        Config::set('services.google_sheets.webhook_url', $webhookUrl);
        Config::set('services.google_sheets.enabled', true);

        // Simulasi webhook server error 500
        Http::fake([
            $webhookUrl => Http::response(['status' => 'error'], 500),
        ]);

        $setoran = app(PencatatSetoran::class)->catat(
            nasabah: $this->nasabah,
            kategori: $this->kategori,
            beratGram: 1000,
            petugas: $this->admin,
        );

        $sync = app(GoogleSheetsSync::class);
        $result = $sync->sync($setoran);

        $this->assertFalse($result); // Mengembalikan false secara aman tanpa crash/exception
    }

    public function test_sync_nonaktif_secara_default_meski_url_terisi(): void
    {
        // Opt-in (R3): URL ada tapi enabled=false → tidak mengirim apa pun.
        Config::set('services.google_sheets.webhook_url', 'https://script.google.com/macros/s/AKfycbz_TEST/exec');
        Config::set('services.google_sheets.enabled', false);
        Http::fake();

        $setoran = app(PencatatSetoran::class)->catat(
            nasabah: $this->nasabah,
            kategori: $this->kategori,
            beratGram: 1000,
            petugas: $this->admin,
        );

        $sync = app(GoogleSheetsSync::class);

        $this->assertFalse($sync->isConfigured());
        $this->assertFalse($sync->sync($setoran));
        Http::assertNothingSent();
    }

    public function test_payload_menyertakan_secret_bila_dikonfigurasi(): void
    {
        $webhookUrl = 'https://script.google.com/macros/s/AKfycbz_TEST/exec';
        Config::set('services.google_sheets.webhook_url', $webhookUrl);
        Config::set('services.google_sheets.enabled', true);
        Config::set('services.google_sheets.secret', 'rahasia-webhook');
        Http::fake([$webhookUrl => Http::response(['status' => 'success'], 200)]);

        $setoran = app(PencatatSetoran::class)->catat(
            nasabah: $this->nasabah,
            kategori: $this->kategori,
            beratGram: 1000,
            petugas: $this->admin,
        );

        app(GoogleSheetsSync::class)->sync($setoran);

        Http::assertSent(fn ($request) => $request['secret'] === 'rahasia-webhook');
    }
}
