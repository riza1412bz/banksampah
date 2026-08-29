<?php

namespace App\Http\Controllers;

use App\Models\HargaSampah;
use App\Models\KategoriSampah;
use App\Models\KelompokSampah;
use App\Services\PengaturHarga;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Pengelolaan harga sampah & kategori (menu admin).
 */
class HargaController extends Controller
{
    public function daftarHarga(Request $request, PengaturHarga $pengatur)
    {
        // Eager-load harga sekaligus (hindari N+1 di hargaAktif()), pilih
        // harga yang sedang berlaku di PHP.
        $kategoriSetoran = KategoriSampah::with([
            'kelompok',
            'harga' => fn ($q) => $q->orderByDesc('berlaku_dari'),
        ])->orderBy('nama')->get()->map(function (KategoriSampah $k) {
            $k->harga_aktif_model = $k->harga->firstWhere('berlaku_sampai', null);

            return $k;
        });

        return view('admin.harga', [
            'kategori' => $kategoriSetoran,
            'riwayat' => HargaSampah::with(['kategori', 'dibuatOleh'])
                ->orderByDesc('berlaku_dari')
                ->orderByDesc('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function ubahHarga(Request $request, PengaturHarga $pengatur)
    {
        $data = $request->validate([
            'kategori_sampah_id' => ['required', 'exists:kategori_sampah,id'],
            'harga_per_kg' => ['required', 'integer', 'min:1', 'max:1000000'],
            'berlaku_dari' => ['nullable', 'date'],
        ], [], [
            'kategori_sampah_id' => 'jenis plastik',
            'harga_per_kg' => 'harga',
        ]);

        $kategori = KategoriSampah::findOrFail($data['kategori_sampah_id']);

        try {
            $pengatur->ubah(
                kategori: $kategori,
                hargaBaruPerKg: (int) $data['harga_per_kg'],
                admin: $request->user(),
                berlakuDari: $data['berlaku_dari'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('gagal', $e->getMessage());
        }

        return redirect()
            ->route('admin.harga.index')
            ->with('sukses', "Harga {$kategori->nama} sekarang Rp ".number_format((int) $data['harga_per_kg'], 0, ',', '.').'/kg. Struk lama tidak berubah.');
    }

    public function initDefaultHarga(Request $request, PengaturHarga $pengatur)
    {
        $count = $pengatur->inisialisasiKategoriTampaHarga($request->user());

        if ($count === 0) {
            return redirect()->route('admin.harga.index')->with('sukses', 'Semua kategori sudah punya harga.');
        }

        return redirect()->route('admin.harga.index')->with('sukses', "{$count} kategori diisi harga default Rp 1.000/kg.");
    }

    public function formKategoriBaru()
    {
        return view('admin.harga-edit-kategori', [
            'k' => new KategoriSampah,
            'kelompok' => KelompokSampah::where('aktif', true)->orderBy('urutan')->get(),
        ]);
    }

    public function formEditKategori(KategoriSampah $kategori)
    {
        return view('admin.harga-edit-kategori', [
            'k' => $kategori,
            'kelompok' => KelompokSampah::where('aktif', true)->orderBy('urutan')->get(),
        ]);
    }

    public function storeKategori(Request $request, PengaturHarga $pengatur)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:80', 'unique:kategori_sampah,nama'],
            'kode' => ['nullable', 'string', 'max:20', 'unique:kategori_sampah,kode'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'kelompok_sampah_id' => ['nullable', 'exists:kelompok_sampah,id'],
            'faktor_emisi_kg_co2e' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'harga_per_kg' => ['required', 'integer', 'min:1', 'max:1000000'],
        ], [], [
            'nama' => 'nama jenis sampah',
            'kode' => 'kode',
            'kelompok_sampah_id' => 'kelompok EPA',
            'faktor_emisi_kg_co2e' => 'faktor emisi',
            'harga_per_kg' => 'harga per kg',
        ]);

        $faktorEmisi = null;
        if (isset($data['faktor_emisi_kg_co2e']) && is_numeric($data['faktor_emisi_kg_co2e'])) {
            $faktorEmisi = (float) $data['faktor_emisi_kg_co2e'];
        } elseif (! empty($data['kelompok_sampah_id'])) {
            $kel = KelompokSampah::find($data['kelompok_sampah_id']);
            $faktorEmisi = $kel ? (float) $kel->ef_recycled : null;
        }

        $kategori = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $faktorEmisi, $request) {
            $kategori = KategoriSampah::create([
                'nama' => $data['nama'],
                'kode' => strtoupper($data['kode'] ?? ''),
                'keterangan' => $data['keterangan'] ?? null,
                'kelompok_sampah_id' => $data['kelompok_sampah_id'] ?? null,
                'faktor_emisi_kg_co2e' => $faktorEmisi,
                'aktif' => true,
            ]);

            HargaSampah::create([
                'kategori_sampah_id' => $kategori->id,
                'harga_per_kg' => (int) $data['harga_per_kg'],
                'berlaku_dari' => now()->toDateString(),
                'berlaku_sampai' => null,
                'dibuat_oleh' => $request->user()->id,
            ]);

            return $kategori;
        });

        return redirect()
            ->route('admin.harga.index')
            ->with('sukses', "Jenis sampah {$kategori->nama} ditambahkan dengan harga Rp ".number_format((int) $data['harga_per_kg'], 0, ',', '.').'/kg.');
    }

    public function updateKategori(Request $request, KategoriSampah $kategori, PengaturHarga $pengatur)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:80', 'unique:kategori_sampah,nama,'.$kategori->id],
            'kode' => ['nullable', 'string', 'max:20', 'unique:kategori_sampah,kode,'.$kategori->id],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'kelompok_sampah_id' => ['nullable', 'exists:kelompok_sampah,id'],
            'faktor_emisi_kg_co2e' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'aktif' => ['nullable', 'in:1,on,true'],
            'harga_per_kg' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ], [], [
            'nama' => 'nama jenis sampah',
            'kode' => 'kode',
            'kelompok_sampah_id' => 'kelompok EPA',
            'faktor_emisi_kg_co2e' => 'faktor emisi',
            'harga_per_kg' => 'harga per kg',
        ]);

        $faktorEmisi = $kategori->faktor_emisi_kg_co2e;
        if (isset($data['faktor_emisi_kg_co2e']) && is_numeric($data['faktor_emisi_kg_co2e'])) {
            $faktorEmisi = (float) $data['faktor_emisi_kg_co2e'];
        } elseif (! empty($data['kelompok_sampah_id'])) {
            $kel = KelompokSampah::find($data['kelompok_sampah_id']);
            $faktorEmisi = $kel ? (float) $kel->ef_recycled : null;
        }

        $kategori->update([
            'nama' => $data['nama'],
            'kode' => strtoupper($data['kode'] ?? ''),
            'keterangan' => $data['keterangan'] ?? null,
            'kelompok_sampah_id' => $data['kelompok_sampah_id'] ?? null,
            'faktor_emisi_kg_co2e' => $faktorEmisi,
            'aktif' => (bool) ($data['aktif'] ?? false),
        ]);

        if (! empty($data['harga_per_kg'])) {
            $hargaSekarang = $kategori->hargaAktif()?->harga_per_kg;
            if ($hargaSekarang === null || (int) $data['harga_per_kg'] !== $hargaSekarang) {
                $pengatur->ubah(
                    kategori: $kategori,
                    hargaBaruPerKg: (int) $data['harga_per_kg'],
                    admin: $request->user(),
                );
            }
        }

        return redirect()
            ->route('admin.harga.index')
            ->with('sukses', "Jenis sampah {$kategori->nama} diperbarui.");
    }

    public function destroyKategori(KategoriSampah $kategori)
    {
        $nama = $kategori->nama;

        // Kategori yang sudah pernah dipakai setoran TIDAK dihapus — hanya
        // dinonaktifkan, supaya riwayat struk tetap utuh. Baris setoran
        // punya FK restrictOnDelete, jadi hapus paksa akan gagal juga.
        if ($kategori->setoran()->exists()) {
            $kategori->update(['aktif' => false]);

            return redirect()
                ->route('admin.harga.index')
                ->with('sukses', "Jenis plastik {$nama} sudah pernah dipakai setoran, jadi dinonaktifkan. Riwayat tetap utuh.");
        }

        $kategori->harga()->delete();
        $kategori->delete();

        return redirect()
            ->route('admin.harga.index')
            ->with('sukses', "Jenis plastik {$nama} dihapus.");
    }
}