<?php

namespace Tests\Feature;

use App\Models\HargaSampah;
use App\Models\KategoriSampah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HargaSampahBaruTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $nasabah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin BSIL',
            'email' => 'admin@bsil.test',
            'password' => 'rahasia123',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->nasabah = User::create([
            'name' => 'Bu Rini',
            'email' => 'rini@warga.test',
            'password' => 'rahasia123',
            'role' => User::ROLE_NASABAH,
            'kode_nasabah' => 'BSIL-0010',
        ]);
    }

    public function test_admin_menambah_kategori_baru_sekaligus_harga_awal(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.harga.store-kategori'), [
                'nama' => 'Aluminium Kaleng',
                'kode' => 'ALU',
                'keterangan' => 'Kaleng minuman aluminium bersih',
                'harga_per_kg' => 12000,
            ])
            ->assertRedirect(route('admin.harga.index'))
            ->assertSessionHas('sukses');

        $kategori = KategoriSampah::where('kode', 'ALU')->first();
        $this->assertNotNull($kategori);
        $this->assertSame('Aluminium Kaleng', $kategori->nama);

        $harga = $kategori->hargaAktif();
        $this->assertNotNull($harga);
        $this->assertSame(12000, $harga->harga_per_kg);
        $this->assertNull($harga->berlaku_sampai);
    }

    public function test_admin_edit_kategori_dan_ubah_harga(): void
    {
        $kategori = KategoriSampah::create([
            'nama' => 'Kardus Tebal',
            'kode' => 'KRD',
            'aktif' => true,
        ]);

        HargaSampah::create([
            'kategori_sampah_id' => $kategori->id,
            'harga_per_kg' => 1500,
            'berlaku_dari' => now()->toDateString(),
            'dibuat_oleh' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.harga.update-kategori', $kategori), [
                'nama' => 'Kardus Cokelat Tebal',
                'kode' => 'KRD-T',
                'aktif' => '1',
                'harga_per_kg' => 2000,
            ])
            ->assertRedirect(route('admin.harga.index'))
            ->assertSessionHas('sukses');

        $kategori->refresh();
        $this->assertSame('Kardus Cokelat Tebal', $kategori->nama);
        $this->assertSame(2000, $kategori->hargaAktif()?->harga_per_kg);
    }

    public function test_form_catat_setor_menampilkan_harga_aktif_dan_peringatan_tanpa_harga(): void
    {
        $kategoriPunyaHarga = KategoriSampah::create([
            'nama' => 'Botol PET bening',
            'kode' => 'PET',
            'aktif' => true,
        ]);

        HargaSampah::create([
            'kategori_sampah_id' => $kategoriPunyaHarga->id,
            'harga_per_kg' => 3000,
            'berlaku_dari' => now()->toDateString(),
            'dibuat_oleh' => $this->admin->id,
        ]);

        $kategoriTanpaHarga = KategoriSampah::create([
            'nama' => 'Sampah Kaca',
            'kode' => 'KACA',
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.setoran.create'))
            ->assertOk();

        $response->assertSee('Rp 3.000/kg');
        $response->assertSee('Belum ada harga');
        $response->assertSee('⚠️ Ada 1 jenis sampah belum punya harga aktif');
    }

    public function test_perubahan_harga_langsung_tampil_di_halaman_catat_setor(): void
    {
        $k1 = KategoriSampah::create(['nama' => 'Botol PET bening', 'kode' => 'PET', 'aktif' => true]);
        $k2 = KategoriSampah::create(['nama' => 'Gelas PP', 'kode' => 'PP', 'aktif' => true]);
        $k3 = KategoriSampah::create(['nama' => 'Besi Tua', 'kode' => 'BESI', 'aktif' => true]);

        HargaSampah::create(['kategori_sampah_id' => $k1->id, 'harga_per_kg' => 3000, 'berlaku_dari' => now()->toDateString(), 'dibuat_oleh' => $this->admin->id]);
        HargaSampah::create(['kategori_sampah_id' => $k2->id, 'harga_per_kg' => 2500, 'berlaku_dari' => now()->toDateString(), 'dibuat_oleh' => $this->admin->id]);
        HargaSampah::create(['kategori_sampah_id' => $k3->id, 'harga_per_kg' => 1000, 'berlaku_dari' => now()->toDateString(), 'dibuat_oleh' => $this->admin->id]);

        $res1 = $this->actingAs($this->admin)->get(route('admin.setoran.create'))->assertOk();
        $res1->assertSee('Rp 3.000/kg');
        $res1->assertSee('Rp 2.500/kg');
        $res1->assertSee('Rp 1.000/kg');

        // Admin ubah harga Besi Tua menjadi Rp 1.500/kg
        $this->actingAs($this->admin)->post(route('admin.harga.ubah'), [
            'kategori_sampah_id' => $k3->id,
            'harga_per_kg' => 1500,
        ])->assertRedirect(route('admin.harga.index'));

        // Cek halaman catat setor langsung menampilkan harga baru
        $res2 = $this->actingAs($this->admin)->get(route('admin.setoran.create'))->assertOk();
        $res2->assertSee('Rp 1.500/kg');
        $res2->assertDontSee('Rp 1.000/kg');
    }
}
