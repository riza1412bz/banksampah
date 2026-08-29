<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alamat terstruktur nasabah dipakai sebagai lokasi otomatis di jadwal setor.
 */
class AlamatNasabahTest extends TestCase
{
    use RefreshDatabase;

    public function test_alamat_lengkap_menggabungkan_semua_bagian(): void
    {
        $nasabah = User::factory()->create([
            'role' => User::ROLE_NASABAH,
            'jalan' => 'Jl. Mawar No. 7',
            'rt_rw' => '001/002',
            'detail_rumah' => 'Blok B',
            'desa_kelurahan' => 'ARJOWINANGUN',
            'kecamatan' => 'KEDUNGKANDANG',
            'kota' => 'KOTA MALANG',
        ]);

        $this->assertSame(
            'Jl. Mawar No. 7, 001/002, Blok B, ARJOWINANGUN, KEDUNGKANDANG, KOTA MALANG',
            $nasabah->alamatLengkap()
        );
    }

    public function test_alamat_lengkap_null_saat_tidak_ada_data(): void
    {
        $nasabah = User::factory()->create(['role' => User::ROLE_NASABAH]);

        $this->assertNull($nasabah->alamatLengkap());
    }

    public function test_alamat_lengkap_mengabaikan_bagian_kosong(): void
    {
        $nasabah = User::factory()->create([
            'role' => User::ROLE_NASABAH,
            'jalan' => 'Jl. Melati',
            'desa_kelurahan' => 'TLOGOWARU',
            'kota' => 'KOTA MALANG',
        ]);

        $this->assertSame('Jl. Melati, TLOGOWARU, KOTA MALANG', $nasabah->alamatLengkap());
    }
}
