<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi R2: generasi kode_nasabah yang race-safe & terpusat (User::buatNasabah).
 */
class KodeNasabahTest extends TestCase
{
    use RefreshDatabase;

    public function test_buat_nasabah_menghasilkan_kode_berurutan(): void
    {
        $a = User::buatNasabah(['name' => 'Ani', 'email' => 'ani@contoh.id', 'password' => 'rahasia123']);
        $b = User::buatNasabah(['name' => 'Budi', 'email' => 'budi@contoh.id', 'password' => 'rahasia123']);

        $this->assertSame('BSIL-0001', $a->kode_nasabah);
        $this->assertSame('BSIL-0002', $b->kode_nasabah);
        $this->assertSame(User::ROLE_NASABAH, $a->role);
        $this->assertTrue($a->aktif);
    }

    public function test_nasabah_corporate_memakai_prefix_corp(): void
    {
        $c = User::buatNasabah(
            ['name' => 'PT Maju', 'email' => 'pt@contoh.id', 'password' => 'rahasia123'],
            User::JENIS_CORPORATE,
        );

        $this->assertSame('CORP-0001', $c->kode_nasabah);
        $this->assertTrue($c->isCorporate());
    }

    public function test_registrasi_mandiri_memakai_jalur_bersama_dan_membuat_kode(): void
    {
        $this->post('/daftar', [
            'name' => 'Citra',
            'email' => 'citra@contoh.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('nasabah.beranda'));

        $this->assertDatabaseHas('users', [
            'email' => 'citra@contoh.id',
            'kode_nasabah' => 'BSIL-0001',
            'role' => User::ROLE_NASABAH,
        ]);
    }
}
