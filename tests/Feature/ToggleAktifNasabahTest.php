<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Toggle aktif/nonaktif nasabah langsung dari kolom Aksi di halaman daftar.
 */
class ToggleAktifNasabahTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_mematikan_dan_menghidupkan_nasabah(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $nasabah = User::factory()->create(['role' => User::ROLE_NASABAH, 'aktif' => true]);

        // Matikan
        $this->actingAs($admin)
            ->put(route('admin.nasabah.toggle-aktif', $nasabah))
            ->assertRedirect(route('admin.nasabah.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('users', ['id' => $nasabah->id, 'aktif' => false]);

        // Hidupkan lagi
        $this->actingAs($admin)
            ->put(route('admin.nasabah.toggle-aktif', $nasabah->fresh()))
            ->assertRedirect(route('admin.nasabah.index'));

        $this->assertDatabaseHas('users', ['id' => $nasabah->id, 'aktif' => true]);
    }

    public function test_toggle_tidak_bisa_untuk_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $adminLain = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->put(route('admin.nasabah.toggle-aktif', $adminLain))
            ->assertNotFound();
    }

    public function test_tombol_toggle_tampil_di_kolom_aksi(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $nasabah = User::factory()->create(['role' => User::ROLE_NASABAH, 'aktif' => true]);
        $nonaktif = User::factory()->create(['role' => User::ROLE_NASABAH, 'aktif' => false]);

        $this->actingAs($admin)
            ->get(route('admin.nasabah.index'))
            ->assertOk()
            ->assertSee(route('admin.nasabah.toggle-aktif', $nasabah))
            ->assertSee('Nonaktifkan '.$nasabah->name)
            ->assertSee('Aktifkan '.$nonaktif->name);
    }
}
