<?php

namespace Tests\Feature;

use App\Models\HargaSampah;
use App\Models\KategoriSampah;
use App\Models\Setoran;
use App\Models\User;
use App\Services\PencatatSetoran;
use App\Services\PengaturHarga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HargaDibekukanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $nasabah;

    private KategoriSampah $pet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'email' => 'admin.uji@banksampah.test',
            'password' => 'rahasia123',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->nasabah = User::create([
            'name' => 'Bu Sri',
            'email' => 'bu.sri@warga.test',
            'password' => 'rahasia123',
            'role' => User::ROLE_NASABAH,
            'kode_nasabah' => 'BSIL-0001',
        ]);

        $this->pet = KategoriSampah::create([
            'kode' => 'PET-UJI',
            'nama' => 'Botol PET bening',
            'aktif' => true,
        ]);

        HargaSampah::create([
            'kategori_sampah_id' => $this->pet->id,
            'harga_per_kg' => 3000,
            'berlaku_dari' => now()->toDateString(),
            'dibuat_oleh' => $this->admin->id,
        ]);
    }

    public function test_membekukan_harga_di_struk_lama_saat_harga_naik(): void
    {
        $catat = app(PencatatSetoran::class);
        $atur = app(PengaturHarga::class);

        // Bu Sri setor 8,2 kg PET saat harga Rp 3.000/kg
        $setoranLama = $catat->catat($this->nasabah, $this->pet, 8200, $this->admin);

        $this->assertSame(3000, $setoranLama->harga_per_kg);
        $this->assertSame(24600, $setoranLama->total_rupiah); // 8,2 x 3000

        // Harga plastik naik jadi Rp 3.500/kg
        $atur->ubah($this->pet->fresh(), 3500, $this->admin);

        // Struk lama HARUS tetap menunjukkan angka aslinya
        $setoranLama->refresh();
        $this->assertSame(3000, $setoranLama->harga_per_kg, 'harga di struk lama ikut berubah');
        $this->assertSame(24600, $setoranLama->total_rupiah, 'total di struk lama ikut berubah');

        // Setoran baru dengan berat sama memakai harga baru
        $setoranBaru = $catat->catat($this->nasabah->fresh(), $this->pet->fresh(), 8200, $this->admin);

        $this->assertSame(3500, $setoranBaru->harga_per_kg);
        $this->assertSame(28700, $setoranBaru->total_rupiah); // 8,2 x 3500
    }

    public function test_menyimpan_riwayat_harga_tanpa_menghapus_yang_lama(): void
    {
        app(PengaturHarga::class)->ubah($this->pet, 3500, $this->admin);

        $riwayat = HargaSampah::where('kategori_sampah_id', $this->pet->id)
            ->orderBy('berlaku_dari')
            ->get();

        $this->assertCount(2, $riwayat);
        $this->assertSame(3000, $riwayat[0]->harga_per_kg);
        $this->assertNotNull($riwayat[0]->berlaku_sampai, 'harga lama belum ditutup');
        $this->assertSame(3500, $riwayat[1]->harga_per_kg);
        $this->assertNull($riwayat[1]->berlaku_sampai, 'harga baru harus aktif');
    }

    public function test_menolak_setoran_kalau_harga_belum_ditetapkan(): void
    {
        $tanpaHarga = KategoriSampah::create([
            'kode' => 'BARU-UJI',
            'nama' => 'Plastik jenis baru',
            'aktif' => true,
        ]);

        $this->expectException(RuntimeException::class);
        app(PencatatSetoran::class)->catat($this->nasabah, $tanpaHarga, 5000, $this->admin);
    }

    public function test_menolak_berat_nol(): void
    {
        $this->expectException(RuntimeException::class);
        app(PencatatSetoran::class)->catat($this->nasabah, $this->pet, 0, $this->admin);
    }

    public function test_nomor_bukti_tidak_mengunci_agregat_dengan_for_update(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        DB::transaction(fn () => Setoran::nomorBuktiBerikutnya(now()));

        $aggregateQueries = array_filter(
            $queries,
            fn (string $query): bool => str_contains($query, 'max(') && str_contains($query, 'setoran'),
        );

        $this->assertNotEmpty($aggregateQueries);
        $this->assertStringNotContainsString('for update', implode(' ', $aggregateQueries));
    }

    public function test_memberi_nomor_bukti_berurutan_per_hari(): void
    {
        $catat = app(PencatatSetoran::class);

        $a = $catat->catat($this->nasabah, $this->pet, 1000, $this->admin);
        $b = $catat->catat($this->nasabah->fresh(), $this->pet->fresh(), 2000, $this->admin);

        $tgl = now()->format('ymd');
        $this->assertSame("BSIL-{$tgl}-0001", $a->nomor_bukti);
        $this->assertSame("BSIL-{$tgl}-0002", $b->nomor_bukti);
    }

    public function test_menjumlahkan_total_tabungan_dari_setoran_yang_dibekukan(): void
    {
        $catat = app(PencatatSetoran::class);

        $catat->catat($this->nasabah, $this->pet, 8200, $this->admin);                    // 24.600
        app(PengaturHarga::class)->ubah($this->pet->fresh(), 3500, $this->admin);
        $catat->catat($this->nasabah->fresh(), $this->pet->fresh(), 4000, $this->admin);  // 14.000

        $nasabah = $this->nasabah->fresh();

        $this->assertSame(12200, $nasabah->totalBeratGram());
        $this->assertSame(38600, $nasabah->totalRupiah());
    }

    public function test_menghitung_berat_kg_dari_gram(): void
    {
        $setoran = app(PencatatSetoran::class)->catat($this->nasabah, $this->pet, 8200, $this->admin);

        $this->assertSame(8.2, $setoran->berat_kg);
    }
}
