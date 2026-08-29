<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Pengelolaan nasabah (menu admin).
 */
class AdminNasabahController extends Controller
{
    public function daftarNasabah(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterJenis = $request->query('jenis', '');

        $nasabah = User::where('role', User::ROLE_NASABAH)
            ->when($cari !== '', fn ($q) => $q->where(function ($w) use ($cari) {
                $w->where('name', 'like', "%{$cari}%")
                    ->orWhere('kode_nasabah', 'like', "%{$cari}%")
                    ->orWhere('email', 'like', "%{$cari}%");
            }))
            ->when(in_array($filterJenis, User::JENIS_NASABAH_OPTIONS, true), fn ($q) => $q->where('jenis_nasabah', $filterJenis))
            ->withSum('setoran as jumlah_berat_gram', 'berat_gram')
            ->withSum('setoran as jumlah_rupiah', 'total_rupiah')
            ->withCount('setoran')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.nasabah', ['nasabah' => $nasabah, 'cari' => $cari, 'filterJenis' => $filterJenis]);
    }

    public function formNasabah()
    {
        return view('admin.nasabah-baru', [
            'daftarKota' => Wilayah::daftarKota(),
            'wilayah' => $this->wilayahBerjenjang(),
        ]);
    }

    public function simpanNasabah(Request $request)
    {
        $data = $this->validasiNasabah($request, null);
        $jenis = $data['jenis_nasabah'] ?? User::JENIS_PERORANGAN;
        unset($data['jenis_nasabah']);

        $nasabah = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $jenis) {
            $prefix = User::prefixUntukJenis($jenis);
            $like = $prefix.'-%';
            try {
                $terakhir = User::where('kode_nasabah', 'like', $like)
                    ->lockForUpdate()
                    ->orderByDesc('kode_nasabah')
                    ->value('kode_nasabah');
            } catch (\Throwable) {
                $terakhir = User::where('kode_nasabah', 'like', $like)->orderByDesc('kode_nasabah')->value('kode_nasabah');
            }
            $urut = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

            return User::create([
                ...$data,
                'role' => User::ROLE_NASABAH,
                'jenis_nasabah' => $jenis,
                'kode_nasabah' => $prefix.'-'.str_pad((string) $urut, 4, '0', STR_PAD_LEFT),
                'aktif' => true,
            ]);
        });

        return redirect()
            ->route('admin.nasabah.index')
            ->with('sukses', "Nasabah {$nasabah->name} ditambahkan dengan kode {$nasabah->kode_nasabah}.");
    }

    public function formEditNasabah(User $nasabah)
    {
        if ($nasabah->role !== User::ROLE_NASABAH) {
            abort(404);
        }

        return view('admin.nasabah-edit', [
            'n' => $nasabah,
            'sudahSetor' => $nasabah->setoran()->exists(),
            'daftarKota' => Wilayah::daftarKota(),
            'wilayah' => $this->wilayahBerjenjang(),
        ]);
    }

    public function updateNasabah(Request $request, User $nasabah)
    {
        if ($nasabah->role !== User::ROLE_NASABAH) {
            abort(404);
        }

        $data = $this->validasiNasabah($request, $nasabah->id);

        $nasabah->update($data);

        return redirect()
            ->route('admin.nasabah.edit', $nasabah)
            ->with('sukses', "Data {$nasabah->name} diperbarui.");
    }

    public function resetSandiNasabah(Request $request, User $nasabah)
    {
        if ($nasabah->role !== User::ROLE_NASABAH) {
            abort(404);
        }

        $data = $request->validate([
            'password' => ['required', Password::min(8)],
        ], [], [
            'password' => 'kata sandi',
        ]);

        $nasabah->update([
            'password' => bcrypt($data['password']),
        ]);

        return redirect()
            ->route('admin.nasabah.edit', $nasabah)
            ->with('sukses', "Sandi {$nasabah->name} direset.");
    }

    public function toggleAktifNasabah(User $nasabah)
    {
        if ($nasabah->role !== User::ROLE_NASABAH) {
            abort(404);
        }

        $nasabah->update(['aktif' => ! $nasabah->aktif]);

        $status = $nasabah->aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('admin.nasabah.index')
            ->with('sukses', "Nasabah {$nasabah->name} {$status}. Setorannya tetap tersimpan.");
    }

    public function destroyNasabah(User $nasabah)
    {
        if ($nasabah->role !== User::ROLE_NASABAH) {
            abort(404);
        }

        $nama = $nasabah->name;

        // CTA hapus hanya boleh jalan kalau nasabah BELUM PERNAH setor sampah.
        // Yang sudah setor: riwayat struk & tabungan wajib utuh, jadi tolak
        // hapus — cukup nonaktifkan lewat toggle.
        if ($nasabah->setoran()->exists()) {
            return redirect()
                ->route('admin.nasabah.edit', $nasabah)
                ->with('gagal', "Nasabah {$nama} sudah pernah setor sampah, jadi tidak bisa dihapus. Nonaktifkan saja supaya riwayatnya tetap utuh.");
        }

        $nasabah->jadwalSetor()->delete();
        $nasabah->delete();

        return redirect()
            ->route('admin.nasabah.index')
            ->with('sukses', "Nasabah {$nama} dihapus.");
    }

    /**
     * Wilayah berjenjang untuk dropdown cascading: kota → kecamatan → desa/kelurahan.
     *
     * @return array<string, array<string, array<int, string>>>
     */
    private function wilayahBerjenjang(): array
    {
        return Wilayah::all(['kota', 'kecamatan', 'desa_kelurahan'])
            ->groupBy('kota')
            ->map(fn ($kecs) => $kecs
                ->groupBy('kecamatan')
                ->map(fn ($desas) => $desas->pluck('desa_kelurahan')->sort()->values()->all())
                ->all())
            ->all();
    }

    /**
     * Validasi data nasabah (NIK wajib + alamat terstruktur wajib).
     *
     * @param  int|null  $kecualiId  id user yang dikecualikan dari cek unik
     * @return array<string, mixed>
     */
    private function validasiNasabah(Request $request, ?int $kecualiId): array
    {
        $unik = fn (string $kolom) => $kecualiId ? "unique:users,{$kolom},{$kecualiId}" : "unique:users,{$kolom}";

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', $unik('email')],
            'telepon' => ['nullable', 'string', 'max:20'],
            'nik' => ['required', 'digits:16', $unik('nik')],
            'jenis_nasabah' => $kecualiId ? ['sometimes', 'in:perorangan,corporate'] : ['sometimes', 'in:perorangan,corporate'],
            'kota' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'desa_kelurahan' => ['required', 'string', 'max:150'],
            'jalan' => ['required', 'string', 'max:255'],
            'rt_rw' => ['required', 'string', 'max:10'],
            'detail_rumah' => ['nullable', 'string', 'max:200'],
            'password' => $kecualiId ? ['sometimes'] : ['required', Password::min(8)],
        ], [], [
            'name' => 'nama',
            'email' => 'email',
            'telepon' => 'nomor HP',
            'nik' => 'NIK',
            'jenis_nasabah' => 'jenis nasabah',
            'kota' => 'kota',
            'kecamatan' => 'kecamatan',
            'desa_kelurahan' => 'desa/kelurahan',
            'jalan' => 'jalan',
            'rt_rw' => 'RT/RW',
            'detail_rumah' => 'detail rumah',
            'password' => 'kata sandi',
        ]);
    }
}