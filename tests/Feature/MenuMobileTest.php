<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu utama menjadi dropdown (hamburger) di HP: link tetap tersedia di
 * panel mobile, tombol aksesibel, dan menu desktop tetap utuh.
 */
class MenuMobileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_melihat_tombol_hamburger_dan_panel_mobile(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="tombol-menu"', false)
            ->assertSee('aria-controls="menu-mobile"', false)
            ->assertSee('id="menu-mobile"', false)
            // Semua link admin tetap ada di panel mobile.
            ->assertSee(route('admin.setoran.create'))
            ->assertSee(route('admin.setoran.index'))
            ->assertSee(route('admin.harga.index'))
            ->assertSee(route('admin.jadwal.index'))
            ->assertSee(route('admin.nasabah.index'))
            // Menu desktop tetap utuh.
            ->assertSee('lg:flex', false);
    }

    public function test_nasabah_melihat_panel_mobile_dengan_menu_nasabah(): void
    {
        $nasabah = User::factory()->create(['role' => User::ROLE_NASABAH]);

        $this->actingAs($nasabah)
            ->get(route('nasabah.beranda'))
            ->assertOk()
            ->assertSee('id="tombol-menu"', false)
            ->assertSee(route('nasabah.kalkulator'))
            ->assertSee(route('nasabah.jadwal'))
            ->assertSee('Keluar');
    }

    public function test_menu_desktop_menyertakan_keluar(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertSee(route('logout'));
    }
}
