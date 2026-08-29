<?php

namespace Tests\Feature;

use App\Models\KategoriSampah;
use App\Models\Setoran;
use App\Models\User;
use App\Services\PencatatSetoran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman admin "Setoran": daftar transaksi per nomor bukti dalam
 * periode tanggal, lengkap dengan link struk.
 */
class SetoranIndexTest extends TestCase
{
    use RefreshDatabase;

    private function buatData(): array
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $nasabah = User::factory()->create(['role' => User::ROLE_NASABAH]);
        $pet = KategoriSampah::create(['nama' => 'PET bening', 'kode' => 'PET', 'aktif' => true]);
        $pp = KategoriSampah::create(['nama' => 'PP kresek', 'kode' => 'PP', 'aktif' => true]);

        $pencatat = app(PencatatSetoran::class);
        $setoran = $pencatat->catatBanyak(
            nasabah: $nasabah,
            items: [
                ['kategori' => $pet, 'berat_gram' => 3000, 'harga_per_kg' => 2000],
                ['kategori' => $pp, 'berat_gram' => 1500, 'harga_per_kg' => 1500],
            ],
            petugas: $admin,
        );

        return [$admin, $setoran];
    }

    public function test_admin_melihat_daftar_setoran_per_bukti(): void
    {
        [$admin, $setoran] = $this->buatData();
        $bukti = $setoran[0]->nomor_bukti;

        $this->actingAs($admin)
            ->get('/admin/setoran')
            ->assertOk()
            ->assertSee('Setoran')
            ->assertSee($bukti)
            ->assertSee('Rp 8.250'); // 3000g PET @2000 + 1500g PP @1500
    }

    public function test_halaman_catat_setor_menampilkan_rumus_dampak(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => User::ROLE_NASABAH]);
        KategoriSampah::create(['nama' => 'PET bening', 'kode' => 'PET', 'aktif' => true]);

        $this->actingAs($admin)
            ->get(route('admin.setoran.create'))
            ->assertOk()
            ->assertSee('Rumus')
            ->assertSee('kg CO₂e = berat (kg) × faktor emisi (kg CO₂e/kg)', false)
            ->assertSee('ekuivalen pohon = kg CO₂e ÷ 60', false)
            ->assertSee('Dampak lingkungan setoran ini');
    }

    public function test_daftar_setoran_filter_oleh_tanggal(): void
    {
        [$admin, $setoran] = $this->buatData();
        $tanggal = $setoran[0]->tanggal_setor->toDateString();
        $bukti = $setoran[0]->nomor_bukti;

        // Periode yang sama → transaksi tampil
        $this->actingAs($admin)
            ->get('/admin/setoran?dari='.$tanggal.'&sampai='.$tanggal)
            ->assertOk()
            ->assertSee($bukti);

        // Periode masa depan → kosong
        $this->actingAs($admin)
            ->get('/admin/setoran?dari=2030-01-01&sampai=2030-01-31')
            ->assertOk()
            ->assertSee('Belum ada setoran di periode ini.')
            ->assertDontSee($bukti);
    }

    public function test_link_struk_terbuka_untuk_admin(): void
    {
        [$admin, $setoran] = $this->buatData();
        $id = $setoran[0]->id;

        $this->actingAs($admin)
            ->get('/admin/setoran')
            ->assertOk()
            ->assertSee('/struk/'.$id);

        $this->actingAs($admin)
            ->get('/struk/'.$id)
            ->assertOk()
            ->assertSee($setoran[0]->nomor_bukti);
    }
}
