<?php

namespace Tests\Feature;

use App\Models\HargaSampah;
use App\Models\KategoriSampah;
use App\Models\Setoran;
use App\Models\User;
use App\Services\PencatatSetoran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hapus jenis plastik dari halaman Harga: kategori yang belum pernah
 * dipakai setoran dihapus total, yang sudah dipakai hanya dinonaktifkan.
 */
class DestroyKategoriTest extends TestCase
{
    use RefreshDatabase;

    public function test_hapus_kategori_tanpa_setoran_menghapus_total(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $kategori = KategoriSampah::create(['nama' => 'Besi tua', 'kode' => 'BESI', 'aktif' => true]);
        HargaSampah::create([
            'kategori_sampah_id' => $kategori->id,
            'harga_per_kg' => 3000,
            'berlaku_dari' => now()->toDateString(),
            'dibuat_oleh' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.harga.destroy-kategori', $kategori))
            ->assertRedirect(route('admin.harga.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseMissing('kategori_sampah', ['id' => $kategori->id]);
        $this->assertDatabaseMissing('harga_sampah', ['kategori_sampah_id' => $kategori->id]);
    }

    public function test_kategori_yang_sudah_dipakai_setoran_hanya_dinonaktifkan(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $nasabah = User::factory()->create(['role' => User::ROLE_NASABAH]);
        $kategori = KategoriSampah::create(['nama' => 'PET bening', 'kode' => 'PET', 'aktif' => true]);

        app(PencatatSetoran::class)->catatBanyak(
            nasabah: $nasabah,
            items: [['kategori' => $kategori, 'berat_gram' => 1000, 'harga_per_kg' => 2000]],
            petugas: $admin,
        );

        $this->actingAs($admin)
            ->delete(route('admin.harga.destroy-kategori', $kategori))
            ->assertRedirect(route('admin.harga.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('kategori_sampah', ['id' => $kategori->id, 'aktif' => false]);
        $this->assertDatabaseHas('setoran', ['kategori_sampah_id' => $kategori->id]);
    }

    public function test_tombol_hapus_tampil_di_halaman_harga(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $kategori = KategoriSampah::create(['nama' => 'Kardus', 'kode' => 'KRD', 'aktif' => true]);

        $this->actingAs($admin)
            ->get(route('admin.harga.index'))
            ->assertOk()
            ->assertSee('Hapus')
            ->assertSee(route('admin.harga.destroy-kategori', $kategori));
    }

    public function test_form_hapus_tampil_di_edit_kategori(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $kategori = KategoriSampah::create(['nama' => 'Kardus', 'kode' => 'KRD', 'aktif' => true]);

        $this->actingAs($admin)
            ->get(route('admin.harga.edit-kategori', $kategori))
            ->assertOk()
            ->assertSee('Hapus jenis plastik ini')
            ->assertSee(route('admin.harga.destroy-kategori', $kategori));
    }
}
