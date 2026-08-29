<?php

namespace Tests\Feature;

use App\Models\KategoriSampah;
use App\Models\Setoran;
use App\Models\User;
use App\Services\PencatatSetoran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bukti multi-item: satu nomor bukti untuk semua baris, dan struk
 * menampilkan rincian per item + total grup.
 */
class StrukGrupTest extends TestCase
{
    use RefreshDatabase;

    public function test_setoran_multi_item_pakai_satu_nomor_bukti(): void
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

        $this->assertCount(2, $setoran);
        $this->assertSame($setoran[0]->nomor_bukti, $setoran[1]->nomor_bukti);

        // Total grup = 4500 gram, 8250 rupiah
        $this->assertSame(4500, array_sum(array_map(fn ($s) => $s->berat_gram, $setoran)));
        $this->assertSame(8250, array_sum(array_map(fn ($s) => $s->total_rupiah, $setoran)));
    }

    public function test_struk_menampilkan_rincian_grup(): void
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

        $res = $this->actingAs($nasabah)
            ->get(route('nasabah.struk', $setoran[0]))
            ->assertOk();

        $res->assertSee('PET bening');
        $res->assertSee('PP kresek');
        $res->assertSee('3,00 kg');
        $res->assertSee('1,50 kg');
        $res->assertSee('Rp 6.000');
        $res->assertSee('Rp 2.250');
        $res->assertSee('Rp 8.250'); // total grup
    }
}
