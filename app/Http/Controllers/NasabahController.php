<?php

namespace App\Http\Controllers;

use App\Models\JadwalSetor;
use App\Models\KategoriSampah;
use App\Models\Setoran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman milik nasabah.
 *
 * ATURAN KERAS: setiap query difilter dari Auth::id(), bukan dari parameter
 * URL. Tidak ada endpoint di sini yang menerima user_id dari browser.
 */
class NasabahController extends Controller
{
    public function beranda(Request $request)
    {
        $nasabah = $request->user();

        $dari = $request->query('dari') ?: null;
        $sampai = $request->query('sampai') ?: null;

        // Pagination per NOMOR BUKTI (bukan per baris) — satu transaksi
        // multi-item tetap tampil sebagai satu kartu, tidak terpotong halaman.
        $grup = Setoran::where('user_id', $nasabah->id)
            ->when($dari || $sampai, fn ($q) => $q->DalamPeriode($dari, $sampai))
            ->selectRaw('nomor_bukti, MAX(tanggal_setor) as tanggal_terakhir, MAX(id) as id_terakhir')
            ->groupBy('nomor_bukti')
            ->orderByDesc('tanggal_terakhir')
            ->orderByDesc('id_terakhir')
            ->paginate(10)
            ->withQueryString();

        $setoran = Setoran::with('kategori')
            ->where('user_id', $nasabah->id)
            ->whereIn('nomor_bukti', $grup->pluck('nomor_bukti'))
            ->get()
            ->groupBy('nomor_bukti');

        $jadwalBerikutnya = JadwalSetor::untukNasabah($nasabah)
            ->mendatang()
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->first();

        // Total tabungan & berat mengikuti periode filter yang dipilih nasabah.
        // Tanpa filter (dari & sampai kosong) = seluruh riwayat (lifetime).
        // Gabung 2 sum menjadi 1 query (setengah roundtrip).
        $agregat = Setoran::where('user_id', $nasabah->id)
            ->when($dari || $sampai, fn ($q) => $q->DalamPeriode($dari, $sampai))
            ->selectRaw('SUM(berat_gram) as b, SUM(total_rupiah) as r')
            ->first();
        $totalBeratGram = (int) ($agregat->b ?? 0);
        $totalRupiah = (int) ($agregat->r ?? 0);

        return view('nasabah.beranda', [
            'nasabah' => $nasabah,
            'grup' => $grup,
            'setoran' => $setoran,
            'totalBeratGram' => $totalBeratGram,
            'totalRupiah' => $totalRupiah,
            'jadwalBerikutnya' => $jadwalBerikutnya,
            'dari' => $dari,
            'sampai' => $sampai,
        ]);
    }

    public function struk(Setoran $setoran)
    {
        // Nasabah hanya boleh membuka struknya sendiri; admin boleh semua.
        $this->authorize('view', $setoran);

        $setoran->load(['user', 'grup.kategori', 'dicatatOleh']);

        return view('nasabah.struk', ['setoran' => $setoran]);
    }

    public function kalkulator()
    {
        // Bulk load harga aktif (1 query) alih-alih N x hargaAktif()
        $kategoriAll = KategoriSampah::where('aktif', true)
            ->orderBy('nama')
            ->get();

        $hargaMap = KategoriSampah::hargaAktifMap($kategoriAll->pluck('id'));

        $kategori = $kategoriAll
            ->map(function (KategoriSampah $k) use ($hargaMap) {
                $k->harga_aktif = $hargaMap[$k->id]?->harga_per_kg ?? 0;

                return $k;
            })
            ->filter(fn (KategoriSampah $k) => $k->harga_aktif > 0)
            ->values();

        // Riwayat harga per kategori untuk grafik area (smooth line + fill)
        // Ambil semua perubahan harga, group by kategori — dipakai dropdown & filter waktu
        $riwayat = \App\Models\HargaSampah::whereIn('kategori_sampah_id', $kategoriAll->pluck('id'))
            ->orderBy('berlaku_dari')
            ->get(['kategori_sampah_id', 'harga_per_kg', 'berlaku_dari'])
            ->groupBy('kategori_sampah_id');

        $chartData = [];
        foreach ($kategoriAll as $kat) {
            $rows = $riwayat->get($kat->id, collect());
            // Jika kategori belum pernah punya harga, tetap beri 1 titik dari harga_aktif hari ini agar grafik tidak kosong
            if ($rows->isEmpty() && ($hargaMap[$kat->id]?->harga_per_kg ?? 0) > 0) {
                $chartData[$kat->id] = [
                    'nama' => $kat->nama,
                    'kode' => $kat->kode,
                    'points' => [
                        ['x' => now()->toDateString(), 'y' => $hargaMap[$kat->id]->harga_per_kg],
                    ],
                ];
            } else {
                $chartData[$kat->id] = [
                    'nama' => $kat->nama,
                    'kode' => $kat->kode,
                    'points' => $rows->map(fn ($r) => [
                        'x' => $r->berlaku_dari?->toDateString() ?? now()->toDateString(),
                        'y' => (int) $r->harga_per_kg,
                    ])->values()->all(),
                ];
            }
        }

        return view('nasabah.kalkulator', [
            'kategori' => $kategori,
            'chartData' => $chartData,
            'kategoriOptions' => $kategoriAll->map(fn ($k) => ['id' => $k->id, 'nama' => $k->nama, 'kode' => $k->kode])->values(),
        ]);
    }
}
