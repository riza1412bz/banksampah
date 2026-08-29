<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable(['kode', 'nama', 'keterangan', 'aktif', 'kelompok_sampah_id', 'faktor_emisi_kg_co2e'])]
class KategoriSampah extends Model
{
    /**
     * Kunci cache untuk harga aktif per kategori. Dibalik nama const supaya
     * PengaturHarga bisa invalidasi dengan kunci yang sama tanpa tahu formatnya.
     */
    public const CACHE_HARGA_AKTIF = 'harga_aktif_';

    /**
     * Memoization per-request: harga aktif yang sudah di-load sekali
     * tidak di-query lagi dalam request yang sama (menghindari N+1
     * saat loop PencatatSetoran / kalkulator).
     *
     * @var array<int, ?HargaSampah>
     */
    private static array $memoHargaAktif = [];

    protected $table = 'kategori_sampah';

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(KelompokSampah::class, 'kelompok_sampah_id');
    }

    public function harga(): HasMany
    {
        return $this->hasMany(HargaSampah::class, 'kategori_sampah_id');
    }

    public function setoran(): HasMany
    {
        return $this->hasMany(Setoran::class, 'kategori_sampah_id');
    }

    /**
     * Harga yang sedang berlaku hari ini, atau null kalau admin belum
     * menetapkan harga untuk kategori ini.
     *
     * Optimasi: memo per-request + Cache::remember id saja (skalar) untuk
     * lintas-request. Seluruh bulk lookup sebaiknya pakai hargaAktifMap().
     */
    public function hargaAktif(): ?HargaSampah
    {
        if (array_key_exists($this->id, self::$memoHargaAktif)) {
            return self::$memoHargaAktif[$this->id];
        }

        // Cache hanya id (skalar), bukan objek model. Menyimpan model yang
        // di-serialize di cache rapuh: kalau proses yang membaca cache tidak
        // bisa me-load kelas model (mis. autoloader/opcache basi), PHP
        // mengembalikan __PHP_Incomplete_Class dan melanggar tipe balikan.
        $id = Cache::remember(
            self::CACHE_HARGA_AKTIF.$this->id,
            now()->addHour(),
            fn () => $this->harga()
                ->whereNull('berlaku_sampai')
                ->latest('berlaku_dari')
                ->value('id'),
        );

        $model = $id ? HargaSampah::find($id) : null;

        return self::$memoHargaAktif[$this->id] = $model;
    }

    /**
     * Bulk lookup harga aktif untuk banyak kategori sekaligus — 1 query
     * + 1 cache multi-get pattern, menghindari N query di loop.
     *
     * @param  iterable<int>  $ids
     * @return array<int, ?HargaSampah>  id => HargaSampah|null
     */
    public static function hargaAktifMap(iterable $ids): array
    {
        $idList = [];
        foreach ($ids as $val) {
            $intVal = (int) $val;
            if ($intVal > 0) {
                $idList[] = $intVal;
            }
        }
        $ids = array_values(array_unique($idList));
        if ($ids === []) {
            return [];
        }

        // Ambil yang sudah memo
        $result = [];
        $miss = [];
        foreach ($ids as $id) {
            if (array_key_exists($id, self::$memoHargaAktif)) {
                $result[$id] = self::$memoHargaAktif[$id];
            } else {
                $miss[] = $id;
            }
        }

        if ($miss === []) {
            return $result;
        }

        // Untuk miss, ambil dari cache/database dalam 1 query
        // Coba ambil id harga aktif per kategori sekaligus
        $hargaIds = HargaSampah::whereIn('kategori_sampah_id', $miss)
            ->whereNull('berlaku_sampai')
            ->selectRaw('kategori_sampah_id, MAX(id) as max_id')
            ->groupBy('kategori_sampah_id')
            ->pluck('max_id', 'kategori_sampah_id');

        // Fallback ke cache individual kalau ingin cache hit, tapi query di atas
        // sudah lebih cepat daripada N x Cache::remember untuk miss.
        $models = $hargaIds->isNotEmpty()
            ? HargaSampah::whereIn('id', $hargaIds->values())->get()->keyBy('kategori_sampah_id')
            : collect();

        foreach ($miss as $id) {
            $hargaId = $hargaIds[$id] ?? null;
            $model = $hargaId ? ($models[$id] ?? null) : null;
            // Warm cache untuk request berikutnya
            if ($hargaId) {
                Cache::put(self::CACHE_HARGA_AKTIF.$id, $hargaId, now()->addHour());
            } else {
                Cache::forget(self::CACHE_HARGA_AKTIF.$id);
            }
            self::$memoHargaAktif[$id] = $model;
            $result[$id] = $model;
        }

        return $result;
    }

    /**
     * Flush memo + cache untuk kategori tertentu (dipanggil PengaturHarga).
     */
    public static function lupakanHargaAktif(int $kategoriId): void
    {
        unset(self::$memoHargaAktif[$kategoriId]);
        Cache::forget(self::CACHE_HARGA_AKTIF.$kategoriId);
    }

    public static function lupakanSemuaMemoHarga(): void
    {
        self::$memoHargaAktif = [];
    }
}
