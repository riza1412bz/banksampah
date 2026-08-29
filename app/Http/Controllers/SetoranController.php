<?php

namespace App\Http\Controllers;

use App\Jobs\ExportSetoranJob;
use App\Models\KategoriSampah;
use App\Models\Setoran;
use App\Models\User;
use App\Services\LaporanSetoran;
use App\Services\PencatatSetoran;
use App\Services\PerhitunganDampak;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Pencatatan & daftar setoran (menu admin).
 */
class SetoranController extends Controller
{
    public function daftarSetoran(Request $request, LaporanSetoran $laporan)
    {
        $dari = $request->query('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->query('sampai', now()->toDateString());
        $cari = trim((string) $request->query('cari', ''));

        return view('admin.setoran-index', [
            'dari' => $dari,
            'sampai' => $sampai,
            'cari' => $cari,
            'transaksi' => $laporan->transaksiPeriode($dari, $sampai, $cari),
        ]);
    }

    public function exportSetoran(Request $request)
    {
        $dari = $request->query('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->query('sampai', now()->toDateString());
        $cari = trim((string) $request->query('cari', ''));

        // Nama file unik per request supaya export yang berjalan paralel
        // tidak saling menimpa.
        $namaFile = 'setoran-'.$dari.'-'.$sampai.'-'.now()->format('YmdHis').'.xlsx';

        ExportSetoranJob::dispatch($dari, $sampai, $cari, $namaFile);

        return redirect()
            ->route('admin.setoran.index', ['dari' => $dari, 'sampai' => $sampai, 'cari' => $cari])
            ->with('sukses', 'Export sedang diproses. Unduh: '.route('admin.setoran.export-download', ['file' => $namaFile]));
    }

    public function downloadExport(Request $request)
    {
        $file = basename((string) $request->query('file', ''));
        $path = storage_path('app/private/exports/'.$file);

        if (! is_file($path)) {
            return back()->with('gagal', 'File export belum siap. Coba lagi beberapa saat.');
        }

        return response()
            ->download($path, $file, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }

    public function formSetoran(PerhitunganDampak $dampak)
    {
        return view('admin.setoran-baru', [
            'nasabah' => User::where('role', User::ROLE_NASABAH)
                ->where('aktif', true)
                ->orderBy('name')
                ->get(),
            'kategori' => $this->kategoriDenganHarga($dampak),
        ]);
    }

    public function simpanSetoran(Request $request, PencatatSetoran $pencatat)
    {
        $campurHarga = null;
        if ($request->campur_harga && ctype_digit((string) $request->campur_harga)) {
            $campurHarga = (int) $request->campur_harga;
        }

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.checked' => ['nullable', 'in:1,on,true'],
            'items.*.berat_kg' => ['nullable', 'numeric', 'min:0.01', 'max:10000'],
            'tanggal_setor' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'campur_harga' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ], [
            'items.required' => 'Pilih minimal satu jenis sampah.',
            'items.*.berat_kg.min' => 'Berat setiap item minimal 0,01 kg.',
        ], [
            'user_id' => 'nasabah',
            'items.*.berat_kg' => 'berat',
            'campur_harga' => 'harga campur',
        ]);

        $nasabah = User::findOrFail($request->user_id);

        $items = [];

        foreach ($request->items ?? [] as $id => $item) {
            if (! isset($item['checked'])) {
                continue;
            }

            $kategori = KategoriSampah::findOrFail((int) $id);
            $beratGram = (int) round(((float) ($item['berat_kg'] ?? 0)) * 1000);

            if ($beratGram <= 0) {
                continue;
            }

            $hargaOverride = null;

            if ($kategori->kode === 'CAMPUR') {
                $hargaOverride = $campurHarga;
            }

            $items[] = [
                'kategori' => $kategori,
                'berat_gram' => $beratGram,
                'harga_per_kg' => $hargaOverride,
            ];
        }

        try {
            $setoran = $pencatat->catatBanyak(
                nasabah: $nasabah,
                items: $items,
                petugas: $request->user(),
                tanggalSetor: $request->tanggal_setor,
                catatanUmum: $request->catatan,
            );

            // Kirim ke Google Sheets secara asinkron (fail-safe)
            \App\Jobs\SyncSetoranToGoogleSheets::dispatch(
                array_map(fn ($s) => $s->id, $setoran)
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('gagal', $e->getMessage());
        }

        $totalRupiah = array_sum(array_map(fn ($s) => $s->total_rupiah, $setoran));
        $beratTotal = array_sum(array_map(fn ($s) => $s->berat_gram, $setoran));

        return redirect()
            ->route('nasabah.struk', $setoran[0])
            ->with('sukses', 'Setoran tercatat. '.number_format($beratTotal / 1000, 1, ',', '.').' kg, Rp '.number_format($totalRupiah, 0, ',', '.').' untuk '.$nasabah->name.'.');
    }

    private function kategoriDenganHarga(PerhitunganDampak $dampak)
    {
        $list = KategoriSampah::with('kelompok')
            ->where('aktif', true)
            ->orderBy('nama')
            ->get();

        $hargaMap = KategoriSampah::hargaAktifMap($list->pluck('id'));

        return $list->map(function (KategoriSampah $k) use ($dampak, $hargaMap) {
            $k->harga_aktif = $hargaMap[$k->id]?->harga_per_kg ?? 0;
            $k->dampak_per_kg = $dampak->perKategori($k);

            return $k;
        });
    }
}