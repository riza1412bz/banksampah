<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NIK wajib + unik dan alamat terstruktur (dropdown Kota → Kecamatan →
 * Desa/Kelurahan) di form tambah & edit nasabah.
 */
class NikAlamatTerstrukturTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Wilayah::insert([
            ['kota' => 'KOTA MALANG', 'kecamatan' => 'KEDUNGKANDANG', 'desa_kelurahan' => 'ARJOWINANGUN'],
            ['kota' => 'KOTA MALANG', 'kecamatan' => 'KEDUNGKANDANG', 'desa_kelurahan' => 'TLOGOWARU'],
            ['kota' => 'KOTA MALANG', 'kecamatan' => 'KLOJEN', 'desa_kelurahan' => 'KIDULDALEM'],
            ['kota' => 'KOTA BATU', 'kecamatan' => 'BUMIAJI', 'desa_kelurahan' => 'BUMIAJI'],
        ]);
    }

    private function dataValid(string $nik = '3507011111222233'): array
    {
        return [
            'name' => 'Siti Aminah',
            'email' => 'siti@contoh.test',
            'telepon' => '081234567890',
            'nik' => $nik,
            'kota' => 'KOTA MALANG',
            'kecamatan' => 'KEDUNGKANDANG',
            'desa_kelurahan' => 'ARJOWINANGUN',
            'jalan' => 'Jl. Mawar No. 7',
            'rt_rw' => '001/002',
            'detail_rumah' => 'Blok B',
            'password' => 'rahasia123',
        ];
    }

    public function test_form_tambah_menampilkan_nik_dan_dropdown_alamat(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.nasabah.create'))
            ->assertOk()
            ->assertSee('name="nik"', false)
            ->assertSee('id="kota"', false)
            ->assertSee('id="kecamatan"', false)
            ->assertSee('id="desa_kelurahan"', false)
            ->assertSee('id="jalan"', false)
            ->assertSee('id="rt_rw"', false)
            ->assertSee('id="detail_rumah"', false)
            ->assertSee('KOTA MALANG')
            ->assertSee('KOTA BATU');
    }

    public function test_simpan_nasabah_membutuhkan_nik(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.nasabah.store'), array_merge($this->dataValid(), ['nik' => '']))
            ->assertSessionHasErrors('nik');

        $this->assertDatabaseMissing('users', ['email' => 'siti@contoh.test']);
    }

    public function test_simpan_nasabah_membutuhkan_alamat_terstruktur(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.nasabah.store'), array_merge($this->dataValid(), ['jalan' => '', 'rt_rw' => '', 'kota' => '']))
            ->assertSessionHasErrors(['jalan', 'rt_rw', 'kota']);

        $this->assertDatabaseMissing('users', ['email' => 'siti@contoh.test']);
    }

    public function test_simpan_nasabah_valid_menyimpan_nik_dan_alamat(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.nasabah.store'), $this->dataValid())
            ->assertRedirect(route('admin.nasabah.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'siti@contoh.test',
            'nik' => '3507011111222233',
            'kota' => 'KOTA MALANG',
            'kecamatan' => 'KEDUNGKANDANG',
            'desa_kelurahan' => 'ARJOWINANGUN',
            'jalan' => 'Jl. Mawar No. 7',
            'rt_rw' => '001/002',
            'detail_rumah' => 'Blok B',
        ]);
    }

    public function test_nik_harus_16_digit(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.nasabah.store'), array_merge($this->dataValid(), ['nik' => '12345']))
            ->assertSessionHasErrors('nik');
    }

    public function test_nik_tidak_boleh_duplikat(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create([
            'role' => User::ROLE_NASABAH,
            'nik' => '3507011111222233',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.nasabah.store'), $this->dataValid())
            ->assertSessionHasErrors('nik');

        $this->assertDatabaseMissing('users', ['email' => 'siti@contoh.test']);
    }

    public function test_form_edit_memuat_nilai_nik_dan_alamat_terisi(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $nasabah = User::factory()->create([
            'role' => User::ROLE_NASABAH,
            'nik' => '3507011111222233',
            'kota' => 'KOTA MALANG',
            'kecamatan' => 'KEDUNGKANDANG',
            'desa_kelurahan' => 'TLOGOWARU',
            'jalan' => 'Jl. Mawar No. 7',
            'rt_rw' => '001/002',
            'detail_rumah' => 'Blok B',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.nasabah.edit', $nasabah))
            ->assertOk()
            ->assertSee('value="3507011111222233"', false)
            ->assertSee('TLOGOWARU')
            ->assertSee('value="Jl. Mawar No. 7"', false)
            ->assertSee('value="001/002"', false);
    }

    public function test_update_nasabah_menyimpan_nik_baru_tanpa_bentrok_sendiri(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $nasabah = User::factory()->create([
            'role' => User::ROLE_NASABAH,
            'nik' => '3507011111222233',
        ]);

        $data = array_merge($this->dataValid(), ['email' => $nasabah->email]);
        $data['nik'] = '3507015555666677';

        $this->actingAs($admin)
            ->put(route('admin.nasabah.update', $nasabah), $data)
            ->assertRedirect(route('admin.nasabah.edit', $nasabah));

        $this->assertDatabaseHas('users', [
            'id' => $nasabah->id,
            'nik' => '3507015555666677',
        ]);
    }

    public function test_update_nasabah_tetap_menolak_nik_duplikat(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lain = User::factory()->create(['role' => User::ROLE_NASABAH, 'nik' => '3507011111222233']);
        $nasabah = User::factory()->create(['role' => User::ROLE_NASABAH, 'nik' => '3507015555666677']);

        $this->actingAs($admin)
            ->put(route('admin.nasabah.update', $nasabah), array_merge($this->dataValid(), [
                'email' => $nasabah->email,
                'nik' => '3507011111222233',
            ]))
            ->assertSessionHasErrors('nik');
    }
}
