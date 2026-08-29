<?php

namespace App\Http\Controllers;

use App\Models\JadwalSetor;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Jadwal setor.
 *
 * Dua sisi:
 * - Admin: kelola jadwal (buat, ubah, hapus) lewat route prefix /admin.
 * - Nasabah: hanya melihat jadwal umum + jadwal khusus dirinya.
 */
class JadwalController extends Controller
{
    // ---------- Sisi nasabah ----------

    public function untukNasabah(Request $request)
    {
        $nasabah = $request->user();

        return view('nasabah.jadwal', [
            'mendatang' => JadwalSetor::untukNasabah($nasabah)
                ->mendatang()
                ->orderBy('tanggal')
                ->orderBy('jam_mulai')
                ->get(),
            'lalu' => JadwalSetor::untukNasabah($nasabah)
                ->sudahLewat()
                ->orderByDesc('tanggal')
                ->limit(5)
                ->get(),
        ]);
    }

    // ---------- Sisi admin ----------

    public function index()
    {
        $nasabah = User::where('role', User::ROLE_NASABAH)
            ->where('aktif', true)
            ->orderBy('name')
            ->get();

        return view('admin.jadwal', [
            'mendatang' => JadwalSetor::with('user')
                ->mendatang()
                ->orderBy('tanggal')
                ->orderBy('jam_mulai')
                ->get(),
            'lalu' => JadwalSetor::with('user')
                ->sudahLewat()
                ->orderByDesc('tanggal')
                ->limit(10)
                ->get(),
            'nasabah' => $nasabah,
            // id nasabah => alamat lengkap, untuk autofill kolom Lokasi saat pilih nasabah.
            'alamatNasabah' => $nasabah->mapWithKeys(
                fn (User $n) => [$n->id => $n->alamatLengkap()]
            )->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        JadwalSetor::create($data);

        return redirect()
            ->route('admin.jadwal.index')
            ->with('sukses', 'Jadwal setor ditambahkan.');
    }

    public function update(Request $request, JadwalSetor $jadwal)
    {
        $data = $this->validasi($request);

        $jadwal->update($data);

        return redirect()
            ->route('admin.jadwal.index')
            ->with('sukses', 'Jadwal setor diperbarui.');
    }

    /** Form edit jadwal (antisipasi salah input). */
    public function edit(JadwalSetor $jadwal)
    {
        $nasabah = User::where('role', User::ROLE_NASABAH)
            ->where('aktif', true)
            ->orderBy('name')
            ->get();

        return view('admin.jadwal-edit', [
            'j' => $jadwal,
            'nasabah' => $nasabah,
            'alamatNasabah' => $nasabah->mapWithKeys(
                fn (User $n) => [$n->id => $n->alamatLengkap()]
            )->all(),
        ]);
    }

    public function destroy(JadwalSetor $jadwal)
    {
        $jadwal->delete();

        return redirect()
            ->route('admin.jadwal.index')
            ->with('sukses', 'Jadwal setor dihapus.');
    }

    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i', 'after:jam_mulai'],
            'lokasi' => ['nullable', 'string', 'max:190'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'jam_selesai.after' => 'Jam selesai harus lebih malam dari jam mulai.',
        ], [
            'user_id' => 'nasabah',
            'tanggal' => 'tanggal',
            'jam_mulai' => 'jam mulai',
            'jam_selesai' => 'jam selesai',
        ]);

        // Kosong di form berarti jadwal umum untuk semua nasabah.
        $data['user_id'] = $data['user_id'] ?: null;

        return $data;
    }
}
