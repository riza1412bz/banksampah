<?php

namespace App\Http\Controllers;

use App\Models\Setoran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Halaman dashboard pengurus bank sampah.
 */
class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $dari = $request->query('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->query('sampai', now()->toDateString());

        // Cache rekap per-periode selama 5 menit — dashboard sering di-refresh
        // admin, tanpa cache akan hit 3 query berat setiap load.
        // PENTING: simpan hanya skalar/array, bukan model Eloquent. Cache file
        // dengan `serializable_classes=false` menolak unserialize model dan
        // menghasilkan __PHP_Incomplete_Class (500 di /admin).
        $cacheKey = "dashboard:rekap:{$dari}:{$sampai}";
        $cached = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($dari, $sampai) {
            $rekapRow = Setoran::dalamPeriode($dari, $sampai)
                ->selectRaw('SUM(berat_gram) as berat_gram, SUM(total_rupiah) as rupiah, COUNT(*) as transaksi, COUNT(DISTINCT user_id) as nasabah_aktif')
                ->first();

            // Ambil per kategori via JOIN agar tidak perlu eager load model kategori saat di-cache
            $perKategoriRaw = DB::table('setoran')
                ->join('kategori_sampah', 'kategori_sampah.id', '=', 'setoran.kategori_sampah_id')
                ->where('setoran.tanggal_setor', '>=', $dari)
                ->where('setoran.tanggal_setor', '<=', $sampai)
                ->groupBy('setoran.kategori_sampah_id', 'kategori_sampah.nama')
                ->orderByDesc(DB::raw('SUM(setoran.total_rupiah)'))
                ->selectRaw('setoran.kategori_sampah_id as kategori_sampah_id, kategori_sampah.nama as kategori_nama, SUM(setoran.berat_gram) as berat_gram, SUM(setoran.total_rupiah) as rupiah, COUNT(*) as jumlah')
                ->get();

            return [
                'rekap' => [
                    'berat_gram' => (int) ($rekapRow->berat_gram ?? 0),
                    'rupiah' => (int) ($rekapRow->rupiah ?? 0),
                    'transaksi' => (int) ($rekapRow->transaksi ?? 0),
                    'nasabah_aktif' => (int) ($rekapRow->nasabah_aktif ?? 0),
                ],
                'perKategori' => $perKategoriRaw->map(fn ($r) => (array) $r)->all(),
            ];
        });

        $rekap = (is_array($cached) && isset($cached['rekap']) && is_array($cached['rekap']))
            ? $cached['rekap']
            : [
                'berat_gram' => 0,
                'rupiah' => 0,
                'transaksi' => 0,
                'nasabah_aktif' => 0,
            ];

        // Ubah array cache kembali ke collection ringan yang kompatibel dengan view
        // View sebelumnya pakai $baris->kategori->nama, sekarang sediakan fallback ke kategori_nama
        $rawPerKategori = (is_array($cached) && isset($cached['perKategori']) && is_iterable($cached['perKategori']))
            ? $cached['perKategori']
            : [];

        $perKategori = collect($rawPerKategori)->map(function ($row) {
            $obj = (object) $row;
            // Buat pseudo relation kategori agar view tidak perlu diubah banyak
            $obj->kategori = (object) ['nama' => $obj->kategori_nama ?? '—'];
            return $obj;
        });

        $terbaru = Setoran::with(['user', 'kategori'])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // Cache total nasabah aktif (jarang berubah, invalidasi manual tidak perlu)
        $totalNasabah = Cache::remember('dashboard:total_nasabah', now()->addMinutes(10), fn () => User::where('role', User::ROLE_NASABAH)->where('aktif', true)->count());

        return view('admin.dashboard', [
            'dari' => $dari,
            'sampai' => $sampai,
            'rekap' => $rekap,
            'perKategori' => $perKategori,
            'terbaru' => $terbaru,
            'totalNasabah' => $totalNasabah,
        ]);
    }
}