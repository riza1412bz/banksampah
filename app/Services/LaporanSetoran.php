<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Query agregat setoran per nomor bukti, dipakai bersama oleh halaman
 * daftar setoran (admin) dan job export Excel supaya hasilnya konsisten.
 *
 * OPTIMASI (2026-08):
 * - Sebelum: 3 query (distinct nomor_bukti + whereIn + N eager) + groupBy di PHP.
 *   Memuat SEMUA baris setoran dalam periode ke memori, lalu agregasi di collection.
 * - Sesudah: 1 query agregat sargable di SQL (GROUP BY nomor_bukti) dengan index
 *   tanggal_setor, tanpa memuat baris individual.
 *   ~ O(1) memory per transaksi bukan per baris, jauh lebih cepat untuk periode besar.
 */
class LaporanSetoran
{
    /**
     * Transaksi per nomor bukti dalam periode, dengan filter pencarian nasabah.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function transaksiPeriode(string $dari, string $sampai, string $cari = '')
    {
        return $this->queryPeriode($dari, $sampai, $cari)
            ->get()
            ->map(fn ($r) => $this->formatRow($r))
            ->values();
    }

    /**
     * Versi cursor/streaming untuk export sangat besar.
     *
     * @return \Generator<int, object>
     */
    public function transaksiPeriodeCursor(string $dari, string $sampai, string $cari = ''): \Generator
    {
        foreach ($this->queryPeriode($dari, $sampai, $cari)->cursor() as $r) {
            yield $this->formatRow($r);
        }
    }

    /**
     * Query builder untuk pagination manual jika admin butuh halaman.
     * Mengembalikan builder yang sudah ter-aggregate, tinggal ->paginate().
     */
    public function queryPeriode(string $dari, string $sampai, string $cari = '')
    {
        $terms = array_values(array_filter(array_map('trim', explode(',', $cari))));

        $driver = DB::connection()->getDriverName();
        $groupConcat = $driver === 'pgsql'
            ? "STRING_AGG(DISTINCT k.nama, ', ')"
            : 'GROUP_CONCAT(DISTINCT k.nama)';

        $q = DB::table('setoran as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->leftJoin('kategori_sampah as k', 'k.id', '=', 's.kategori_sampah_id')
            ->where('s.tanggal_setor', '>=', $dari)
            ->where('s.tanggal_setor', '<=', $sampai);

        if ($terms !== []) {
            $q->where(function ($w) use ($terms) {
                foreach ($terms as $term) {
                    $escaped = addcslashes($term, '%_');
                    $w->orWhere('u.name', 'like', "%{$escaped}%");
                }
            });
        }

        return $q->groupBy('s.nomor_bukti')
            ->selectRaw("
                s.nomor_bukti,
                MIN(s.id) as id,
                MAX(s.tanggal_setor) as tanggal,
                MAX(u.name) as nama,
                SUM(s.berat_gram) / 1000.0 as berat_kg,
                SUM(s.total_rupiah) as rupiah,
                COUNT(*) as jumlah_item,
                {$groupConcat} as jenis
            ")
            ->orderByDesc('tanggal')
            ->orderByDesc('id');
    }

    private function formatRow(object $r): object
    {
        try {
            $tgl = Carbon::parse($r->tanggal);
        } catch (\Throwable) {
            $tgl = $r->tanggal;
        }

        return (object) [
            'nomor_bukti' => $r->nomor_bukti,
            'tanggal' => $tgl,
            'nama' => $r->nama ?? '—',
            'jenis' => $r->jenis ?? '—',
            'berat_kg' => (float) $r->berat_kg,
            'rupiah' => (int) $r->rupiah,
            'jumlah_item' => (int) $r->jumlah_item,
            'id' => (int) $r->id,
        ];
    }
}
