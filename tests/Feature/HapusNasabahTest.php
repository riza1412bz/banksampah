<?php

namespace Tests\Feature;

use App\Models\KategoriSampah;
use App\Models\User;
use App\Services\PencatatSetoran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hapus nasabah dari halaman edit: hanya boleh kalau belum pernah setor.
 */
class HapusNasabahTest extends TestCase
{
    use RefreshDatabase;

    public function test_nasabah_tanpa_setoran_bisa_dihapus_permanen(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $nasabah = User::factory()->create(['role' => User::ROLE_NASABAH]);

        $this->actingAs($admin)
            ->delete(route('admin.nasabah.destroy', $nasabah))
            ->assertRedirect(route('admin.nasabah.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseMissing('users', ['id' => $nasabah->id]);
    }

    public function test_nasabah_yang_sudah_setor_tidak_bisa_dihapus(): void
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
            ->delete(route('admin.nasabah.destroy', $nasabah))
            ->assertRedirect(route('admin.nasabah.edit', $nasabah))
            ->assertSessionHas('gagal');

        // Nasabah tetap ada, setorannya utuh
        $this->assertDatabaseHas('users', ['id' => $nasabah->id]);
        $this->assertDatabaseHas('setoran', ['user_id' => $nasabah->id]);
    }

    public function test_cta_hapus_tampil_hanya_untuk_nasabah_belum_setor(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $baru = User::factory()->create(['role' => User::ROLE_NASABAH]);
        $sudah = User::factory()->create(['role' => User::ROLE_NASABAH]);
        $kategori = KategoriSampah::create(['nama' => 'PET bening', 'kode' => 'PET', 'aktif' => true]);

        app(PencatatSetoran::class)->catatBanyak(
            nasabah: $sudah,
            items: [['kategori' => $kategori, 'berat_gram' => 1000, 'harga_per_kg' => 2000]],
            petugas: $admin,
        );

        $this->actingAs($admin)
            ->get(route('admin.nasabah.edit', $baru))
            ->assertOk()
            ->assertSee('Hapus nasabah ini')
            ->assertSee(route('admin.nasabah.destroy', $baru));

        $this->actingAs($admin)
            ->get(route('admin.nasabah.edit', $sudah))
            ->assertOk()
            ->assertDontSee('Hapus nasabah ini')
            ->assertSee('sudah pernah setor sampah');
    }
}
