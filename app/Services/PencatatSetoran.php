<?php

namespace App\Services;

use App\Models\KategoriSampah;
use App\Models\Setoran;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Satu-satunya jalan membuat setoran.
 *
 * Tugas utamanya MEMBEKUKAN harga: harga_per_kg diambil dari harga yang
 * berlaku saat ini lalu disalin ke baris setoran. Setelah itu, perubahan
 * harga di tabel harga_sampah tidak pernah mengubah setoran lama.
 *
 * Untuk Campur custom, harga override bisa dikirim langsung dan lookup
 * harga otomatis dilewati.
 */
class PencatatSetoran
{
    /**
     * Satu setoran — satu jenis.
     *
     * @param  ?int  $hargaOverride  Kalau diset, lookup harga ke DB dilewati.
     *                                Dipakai untuk Campur custom.
     */
    public function catat(
        User $nasabah,
        KategoriSampah $kategori,
        int $beratGram,
        ?User $petugas = null,
        ?string $tanggalSetor = null,
        ?string $catatan = null,
        ?int $hargaOverride = null,
    ): Setoran {
        if ($beratGram <= 0) {
            throw new RuntimeException('Berat harus lebih dari 0 gram.');
        }

        if (! $kategori->aktif) {
            throw new RuntimeException("Kategori {$kategori->kode} sedang tidak aktif.");
        }

        $hargaPerKg = $hargaOverride;

        if ($hargaPerKg === null) {
            $harga = $kategori->hargaAktif();

            if (! $harga) {
                throw new RuntimeException(
                    "Harga untuk {$kategori->nama} belum ditetapkan. Tetapkan harga dulu."
                );
            }

            $hargaPerKg = $harga->harga_per_kg;
        }

        $tanggal = $tanggalSetor ? Carbon::parse($tanggalSetor) : Carbon::today();

        return DB::transaction(function () use ($nasabah, $kategori, $beratGram, $hargaPerKg, $petugas, $tanggal, $catatan) {
            return Setoran::create([
                'nomor_bukti' => Setoran::nomorBuktiBerikutnya($tanggal),
                'user_id' => $nasabah->id,
                'kategori_sampah_id' => $kategori->id,
                'berat_gram' => $beratGram,
                'harga_per_kg' => $hargaPerKg,
                'total_rupiah' => Setoran::hitungTotal($beratGram, $hargaPerKg),
                'tanggal_setor' => $tanggal->toDateString(),
                'dicatat_oleh' => $petugas?->id,
                'catatan' => $catatan,
            ]);
        });
    }

    /**
     * Catat beberapa setoran sekaligus (satu nasabah, satu tanggal).
     *
     * Optimasi: bulk preload hargaAktif (1 query) + single INSERT batch,
     * nomor bukti diambil dengan SELECT ... FOR UPDATE di dalam transaksi.
     *
     * @param  array  $items  Setiap item: ['kategori' => KategoriSampah, 'berat_gram' => int, 'harga_per_kg' => ?int]
     * @return array<Setoran>  Semua setoran yang berhasil dibuat.
     */
    public function catatBanyak(
        User $nasabah,
        array $items,
        ?User $petugas = null,
        ?string $tanggalSetor = null,
        ?string $catatanUmum = null,
    ): array {
        if (empty($items)) {
            throw new RuntimeException('Tidak ada item yang dipilih.');
        }

        $tanggal = $tanggalSetor ? Carbon::parse($tanggalSetor) : Carbon::today();

        // Preload harga aktif untuk semua kategori yang harga_per_kg-nya null
        // dalam 1 query, bukan N query di dalam loop.
        $idsButuhHarga = [];
        foreach ($items as $it) {
            if (! isset($it['harga_per_kg']) || $it['harga_per_kg'] === null) {
                $idsButuhHarga[] = $it['kategori']->id;
            }
        }
        $hargaMap = $idsButuhHarga !== []
            ? \App\Models\KategoriSampah::hargaAktifMap($idsButuhHarga)
            : [];

        return DB::transaction(function () use ($nasabah, $items, $petugas, $tanggal, $catatanUmum, $hargaMap) {
            $setoran = [];

            // Satu nomor bukti untuk SEMUA item batch ini — jadi struk bisa
            // menampilkan satu setoran multi-item sebagai satu bukti.
            // lockForUpdate di dalam nomorBuktiBerikutnya mencegah duplikasi paralel.
            $nomorBukti = Setoran::nomorBuktiBerikutnya($tanggal);

            $now = now();
            $rowsToInsert = [];

            foreach ($items as $item) {
                /** @var KategoriSampah $kategori */
                $kategori = $item['kategori'];
                $beratGram = (int) $item['berat_gram'];
                // Gunakan override jika ada, else bulk map, else fallback (seharusnya tidak terjadi)
                $hargaPerKg = $item['harga_per_kg']
                    ?? $hargaMap[$kategori->id]?->harga_per_kg
                    ?? $kategori->hargaAktif()?->harga_per_kg;

                if ($beratGram <= 0) {
                    continue; // skip baris tanpa berat
                }

                if (! $kategori->aktif) {
                    throw new RuntimeException("Kategori {$kategori->kode} sedang tidak aktif.");
                }

                if (! $hargaPerKg || $hargaPerKg <= 0) {
                    throw new RuntimeException(
                        "Harga untuk {$kategori->nama} belum ditetapkan atau tidak valid."
                    );
                }

                $rowsToInsert[] = [
                    'nomor_bukti' => $nomorBukti,
                    'user_id' => $nasabah->id,
                    'kategori_sampah_id' => $kategori->id,
                    'berat_gram' => $beratGram,
                    'harga_per_kg' => $hargaPerKg,
                    'total_rupiah' => Setoran::hitungTotal($beratGram, $hargaPerKg),
                    'tanggal_setor' => $tanggal->toDateString(),
                    'dicatat_oleh' => $petugas?->id,
                    'catatan' => $catatanUmum,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (empty($rowsToInsert)) {
                throw new RuntimeException('Tidak ada setoran yang valid — isi berat untuk setiap item yang dicentang.');
            }

            // Single bulk insert jauh lebih cepat daripada N x INSERT (1 roundtrip)
            Setoran::insert($rowsToInsert);

            // Ambil kembali models yang baru saja diinsert untuk return value
            // (dipakai controller untuk redirect ke struk pertama)
            $setoran = Setoran::where('nomor_bukti', $nomorBukti)
                ->where('user_id', $nasabah->id)
                ->where('tanggal_setor', $tanggal->toDateString())
                ->orderBy('id')
                ->get()
                // Filter hanya yang barusan: created_at == $now (presisi detik)
                // Untuk keamanan, ambil N terakhir yang sesuai nomor bukti.
                // Jika ada race dengan bukti sama jam sama, ambil slice terakhir.
                ->slice(-count($rowsToInsert))
                ->values()
                ->all();

            // Fallback jika slice logic meleset (mis. transaksi lama nomor sama),
            // cukup kembalikan dalam bentuk collection dari insert terakhir via id desc
            if (count($setoran) !== count($rowsToInsert)) {
                $setoran = Setoran::where('nomor_bukti', $nomorBukti)
                    ->orderByDesc('id')
                    ->limit(count($rowsToInsert))
                    ->get()
                    ->reverse()
                    ->values()
                    ->all();
            }

            return $setoran;
        });
    }
}
