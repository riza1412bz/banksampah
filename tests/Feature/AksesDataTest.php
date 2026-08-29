<?php

namespace Tests\Feature;

use App\Models\HargaSampah;
use App\Models\KategoriSampah;
use App\Models\User;
use App\Services\PencatatSetoran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aturan keras sistem ini: nasabah hanya boleh melihat datanya sendiri.
 * Uang orang lain tidak boleh terlihat, walau URL-nya ditebak.
 */
class AksesDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $sri;

    private User $budi;

    private KategoriSampah $pet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin BSIL',
            'email' => 'admin@bsil.test',
            'password' => 'rahasia123',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->sri = User::create([
            'name' => 'Bu Sri',
            'email' => 'sri@warga.test',
            'password' => 'rahasia123',
            'role' => User::ROLE_NASABAH,
            'kode_nasabah' => 'BSIL-0001',
        ]);

        $this->budi = User::create([
            'name' => 'Pak Budi',
            'email' => 'budi@warga.test',
            'password' => 'rahasia123',
            'role' => User::ROLE_NASABAH,
            'kode_nasabah' => 'BSIL-0002',
        ]);

        $this->pet = KategoriSampah::create(['kode' => 'PET', 'nama' => 'Botol PET bening', 'aktif' => true]);

        HargaSampah::create([
            'kategori_sampah_id' => $this->pet->id,
            'harga_per_kg' => 3000,
            'berlaku_dari' => now()->toDateString(),
            'dibuat_oleh' => $this->admin->id,
        ]);
    }

    private function setorUntuk(User $nasabah, int $gram = 8200)
    {
        return app(PencatatSetoran::class)->catat($nasabah, $this->pet->fresh(), $gram, $this->admin);
    }

    public function test_nasabah_tidak_bisa_membuka_struk_nasabah_lain(): void
    {
        $strukBudi = $this->setorUntuk($this->budi);

        $this->actingAs($this->sri)
            ->get(route('nasabah.struk', $strukBudi))
            ->assertForbidden();
    }

    public function test_nasabah_bisa_membuka_struknya_sendiri(): void
    {
        $strukSri = $this->setorUntuk($this->sri);

        $this->actingAs($this->sri)
            ->get(route('nasabah.struk', $strukSri))
            ->assertOk()
            ->assertSee('Bu Sri')
            ->assertSee($strukSri->nomor_bukti);
    }

    public function test_admin_bisa_membuka_struk_siapa_saja(): void
    {
        $strukBudi = $this->setorUntuk($this->budi);

        $this->actingAs($this->admin)
            ->get(route('nasabah.struk', $strukBudi))
            ->assertOk()
            ->assertSee('Pak Budi');
    }

    public function test_beranda_nasabah_hanya_menampilkan_setoran_sendiri(): void
    {
        $this->setorUntuk($this->sri, 8200);   // Rp 24.600
        $this->setorUntuk($this->budi, 5000);  // Rp 15.000

        $this->actingAs($this->sri)
            ->get(route('nasabah.beranda'))
            ->assertOk()
            ->assertSee('24.600')
            ->assertDontSee('15.000');
    }

    public function test_nasabah_tidak_bisa_masuk_halaman_admin(): void
    {
        $this->actingAs($this->sri)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->sri)->get(route('admin.harga.index'))->assertForbidden();
        $this->actingAs($this->sri)->get(route('admin.nasabah.index'))->assertForbidden();
        $this->actingAs($this->sri)->get(route('admin.setoran.create'))->assertForbidden();
    }

    public function test_admin_bisa_masuk_halaman_admin(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.harga.index'))->assertOk();
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get(route('nasabah.beranda'))->assertRedirect(route('masuk'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('masuk'));
    }

    public function test_pendaftaran_tidak_bisa_menjadikan_diri_admin(): void
    {
        // Walau form dikirim dengan role=admin, hasilnya tetap nasabah.
        $this->post(route('daftar'), [
            'name' => 'Penyusup',
            'email' => 'penyusup@warga.test',
            'password' => 'rahasia12345',
            'password_confirmation' => 'rahasia12345',
            'role' => 'admin',
        ])->assertRedirect(route('nasabah.beranda'));

        $this->assertSame(
            User::ROLE_NASABAH,
            User::where('email', 'penyusup@warga.test')->value('role')
        );
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        $this->sri->update(['aktif' => false]);

        $this->post(route('masuk'), [
            'email' => 'sri@warga.test',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_mencatat_setoran_lalu_diarahkan_ke_struk(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.setoran.store'), [
                'user_id' => $this->sri->id,
                'items' => [
                    $this->pet->id => [
                        'checked' => '1',
                        'berat_kg' => '8.2',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('setoran', [
            'user_id' => $this->sri->id,
            'berat_gram' => 8200,
            'harga_per_kg' => 3000,
            'total_rupiah' => 24600,
        ]);
    }

    public function test_nasabah_tidak_bisa_mencatat_setoran(): void
    {
        $this->actingAs($this->sri)
            ->post(route('admin.setoran.store'), [
                'user_id' => $this->sri->id,
                'items' => [
                    $this->pet->id => [
                        'checked' => '1',
                        'berat_kg' => '999',
                    ],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('setoran', 0);
    }
}
