<?php

namespace Tests\Feature;

use App\Models\JadwalSetor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalSetorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $sri;

    private User $budi;

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
    }

    public function test_nasabah_melihat_jadwal_umum_dan_jadwalnya_sendiri(): void
    {
        JadwalSetor::create([
            'user_id' => null,
            'tanggal' => now()->addDays(3)->toDateString(),
            'lokasi' => 'Balai RW 03',
        ]);

        JadwalSetor::create([
            'user_id' => $this->sri->id,
            'tanggal' => now()->addDays(5)->toDateString(),
            'lokasi' => 'Rumah Bu Sri',
        ]);

        $this->actingAs($this->sri)
            ->get(route('nasabah.jadwal'))
            ->assertOk()
            ->assertSee('Balai RW 03')
            ->assertSee('Rumah Bu Sri');
    }

    public function test_nasabah_tidak_melihat_jadwal_khusus_nasabah_lain(): void
    {
        JadwalSetor::create([
            'user_id' => $this->budi->id,
            'tanggal' => now()->addDays(4)->toDateString(),
            'lokasi' => 'Warung Pak Budi',
        ]);

        $this->actingAs($this->sri)
            ->get(route('nasabah.jadwal'))
            ->assertOk()
            ->assertDontSee('Warung Pak Budi');
    }

    public function test_jadwal_yang_sudah_lewat_tidak_masuk_daftar_mendatang(): void
    {
        JadwalSetor::create([
            'user_id' => null,
            'tanggal' => now()->subDays(7)->toDateString(),
            'lokasi' => 'Lokasi Lampau',
        ]);

        $mendatang = JadwalSetor::untukNasabah($this->sri)->mendatang()->get();

        $this->assertCount(0, $mendatang);
    }

    public function test_admin_bisa_menambah_jadwal_umum(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.jadwal.store'), [
                'user_id' => '',
                'tanggal' => now()->addDays(2)->toDateString(),
                'jam_mulai' => '08:00',
                'jam_selesai' => '11:00',
                'lokasi' => 'Balai RW 03',
            ])
            ->assertRedirect(route('admin.jadwal.index'));

        $this->assertDatabaseHas('jadwal_setor', [
            'user_id' => null,
            'lokasi' => 'Balai RW 03',
        ]);
    }

    public function test_jam_selesai_harus_setelah_jam_mulai(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.jadwal.store'), [
                'tanggal' => now()->addDays(2)->toDateString(),
                'jam_mulai' => '11:00',
                'jam_selesai' => '08:00',
            ])
            ->assertSessionHasErrors('jam_selesai');

        $this->assertDatabaseCount('jadwal_setor', 0);
    }

    public function test_admin_bisa_menghapus_jadwal(): void
    {
        $jadwal = JadwalSetor::create([
            'user_id' => null,
            'tanggal' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.jadwal.destroy', $jadwal))
            ->assertRedirect(route('admin.jadwal.index'));

        $this->assertDatabaseCount('jadwal_setor', 0);
    }

    public function test_nasabah_tidak_bisa_mengelola_jadwal(): void
    {
        $jadwal = JadwalSetor::create([
            'user_id' => null,
            'tanggal' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($this->sri)->get(route('admin.jadwal.index'))->assertForbidden();

        $this->actingAs($this->sri)->post(route('admin.jadwal.store'), [
            'tanggal' => now()->addDays(9)->toDateString(),
        ])->assertForbidden();

        $this->actingAs($this->sri)->delete(route('admin.jadwal.destroy', $jadwal))->assertForbidden();

        $this->assertDatabaseCount('jadwal_setor', 1);
    }

    public function test_rentang_jam_diformat_untuk_dibaca(): void
    {
        $penuh = JadwalSetor::create([
            'tanggal' => now()->addDay()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '11:00',
        ]);

        $mulaiSaja = JadwalSetor::create([
            'tanggal' => now()->addDays(2)->toDateString(),
            'jam_mulai' => '09:30',
        ]);

        $tanpaJam = JadwalSetor::create([
            'tanggal' => now()->addDays(3)->toDateString(),
        ]);

        $this->assertSame('08:00–11:00', $penuh->rentangJam());
        $this->assertSame('09:30 – selesai', $mulaiSaja->rentangJam());
        $this->assertNull($tanpaJam->rentangJam());
    }

    public function test_beranda_menampilkan_jadwal_terdekat(): void
    {
        JadwalSetor::create([
            'user_id' => null,
            'tanggal' => now()->addDays(9)->toDateString(),
            'lokasi' => 'Jadwal Jauh',
        ]);

        JadwalSetor::create([
            'user_id' => null,
            'tanggal' => now()->addDays(2)->toDateString(),
            'lokasi' => 'Jadwal Dekat',
        ]);

        $this->actingAs($this->sri)
            ->get(route('nasabah.beranda'))
            ->assertOk()
            ->assertSee('Jadwal Dekat')
            ->assertDontSee('Jadwal Jauh');
    }

    // ---------- Tabel jadwal + lokasi otomatis dari alamat nasabah ----------

    public function test_admin_melihat_jadwal_khusus_dengan_lokasi_alamat_nasabah(): void
    {
        $this->sri->update([
            'jalan' => 'Jl. Mawar No. 7',
            'rt_rw' => '001/002',
            'desa_kelurahan' => 'ARJOWINANGUN',
            'kecamatan' => 'KEDUNGKANDANG',
            'kota' => 'KOTA MALANG',
        ]);

        JadwalSetor::create([
            'user_id' => $this->sri->id,
            'tanggal' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal.index'))
            ->assertOk()
            ->assertSee('Jl. Mawar No. 7')
            ->assertSee('ARJOWINANGUN')
            ->assertSee('KOTA MALANG');
    }

    public function test_admin_melihat_jadwal_umum_dengan_lokasi_manual(): void
    {
        JadwalSetor::create([
            'user_id' => null,
            'tanggal' => now()->addDays(2)->toDateString(),
            'lokasi' => 'Balai RW 03',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal.index'))
            ->assertOk()
            ->assertSee('Balai RW 03');
    }

    public function test_admin_melihat_lokasi_manual_saat_alamat_nasabah_belum_ada(): void
    {
        // Bu Sri belum punya alamat terstruktur — fallback ke lokasi manual.
        JadwalSetor::create([
            'user_id' => $this->sri->id,
            'tanggal' => now()->addDays(4)->toDateString(),
            'lokasi' => 'Rumah Bu Sri',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal.index'))
            ->assertOk()
            ->assertSee('Rumah Bu Sri');
    }

    public function test_form_jadwal_admin_menyediakan_data_alamat_untuk_autofill(): void
    {
        $this->sri->update([
            'jalan' => 'Jl. Melati 12',
            'desa_kelurahan' => 'TLOGOWARU',
            'kota' => 'KOTA MALANG',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal.index'))
            ->assertOk()
            ->assertSee('Jl. Melati 12')
            ->assertSee('TLOGOWARU');
    }

    public function test_nasabah_melihat_jadwal_khususnya_dengan_alamat_sendiri(): void
    {
        $this->sri->update([
            'jalan' => 'Jl. Anggrek No. 3',
            'rt_rw' => '002/001',
            'desa_kelurahan' => 'KIDULDALEM',
            'kecamatan' => 'KLOJEN',
            'kota' => 'KOTA MALANG',
        ]);

        JadwalSetor::create([
            'user_id' => $this->sri->id,
            'tanggal' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($this->sri)
            ->get(route('nasabah.jadwal'))
            ->assertOk()
            ->assertSee('Jl. Anggrek No. 3')
            ->assertSee('KIDULDALEM')
            ->assertSee('KLOJEN');
    }

    // ---------- Edit jadwal (antisipasi salah input) ----------

    public function test_admin_melihat_tombol_edit_di_tabel_jadwal(): void
    {
        $jadwal = JadwalSetor::create([
            'user_id' => null,
            'tanggal' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal.index'))
            ->assertOk()
            ->assertSee(route('admin.jadwal.edit', $jadwal));
    }

    public function test_admin_bisa_membuka_form_edit_dengan_nilai_terisi(): void
    {
        $jadwal = JadwalSetor::create([
            'user_id' => $this->sri->id,
            'tanggal' => now()->addDays(3)->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '11:00',
            'lokasi' => 'Balai RW 03',
            'keterangan' => 'Bawa sampah terpilah',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal.edit', $jadwal))
            ->assertOk()
            ->assertSee('Edit jadwal')
            ->assertSee(now()->addDays(3)->format('Y-m-d'))
            ->assertSee('08:00')
            ->assertSee('11:00')
            ->assertSee('Balai RW 03')
            ->assertSee('Bawa sampah terpilah')
            ->assertSee($this->sri->name);
    }

    public function test_admin_bisa_memperbaiki_jadwal_yang_salah_input(): void
    {
        $jadwal = JadwalSetor::create([
            'user_id' => $this->sri->id,
            'tanggal' => now()->addDays(3)->toDateString(),
            'jam_mulai' => '08:00',
            'lokasi' => 'Salah ketik lokasi',
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.jadwal.update', $jadwal), [
                'user_id' => $this->sri->id,
                'tanggal' => now()->addDays(5)->toDateString(),
                'jam_mulai' => '09:00',
                'jam_selesai' => '12:00',
                'lokasi' => 'Balai RW 03',
                'keterangan' => 'Koreksi jadwal',
            ])
            ->assertRedirect(route('admin.jadwal.index'))
            ->assertSessionHas('sukses');

        $jadwal->refresh();

        $this->assertSame(now()->addDays(5)->toDateString(), $jadwal->tanggal->toDateString());
        $this->assertSame('09:00', $jadwal->jam_mulai);
        $this->assertSame('12:00', $jadwal->jam_selesai);
        $this->assertSame('Balai RW 03', $jadwal->lokasi);
        $this->assertSame('Koreksi jadwal', $jadwal->keterangan);
    }

    public function test_nasabah_tidak_bisa_membuka_form_edit_jadwal(): void
    {
        $jadwal = JadwalSetor::create([
            'user_id' => null,
            'tanggal' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($this->sri)
            ->get(route('admin.jadwal.edit', $jadwal))
            ->assertForbidden();
    }
}
