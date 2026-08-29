<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Satu baris = satu kali setor satu jenis plastik.
 *
 * harga_per_kg & total_rupiah DIBEKUKAN di baris ini (lihat migrasi).
 * Jangan pernah menghitung ulang total dari tabel harga_sampah — harga
 * plastik fluktuatif dan struk lama harus tetap menunjukkan angka aslinya.
 */
#[Fillable([
    'nomor_bukti', 'user_id', 'kategori_sampah_id', 'berat_gram',
    'harga_per_kg', 'total_rupiah', 'tanggal_setor', 'dicatat_oleh', 'catatan',
])]
class Setoran extends Model
{
    protected $table = 'setoran';

    protected function casts(): array
    {
        return [
            'tanggal_setor' => 'date',
            'berat_gram' => 'integer',
            'harga_per_kg' => 'integer',
            'total_rupiah' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSampah::class, 'kategori_sampah_id');
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    /**
     * Semua baris setoran yang berbagi nomor bukti yang sama (satu transaksi
     * multi-item). Untuk setoran lama yang satu baris satu bukti, hasilnya
     * hanya berisi dirinya sendiri.
     */
    public function grup(): HasMany
    {
        return $this->hasMany(Setoran::class, 'nomor_bukti', 'nomor_bukti')
            ->orderBy('id');
    }

    /** Berat dalam kg untuk ditampilkan, mis. 8.2 */
    public function getBeratKgAttribute(): float
    {
        return $this->berat_gram / 1000;
    }

    /**
     * Hitung total rupiah dari berat + harga. Pembulatan ke rupiah terdekat.
     * Dipakai saat membuat setoran baru, bukan saat membaca yang lama.
     */
    public static function hitungTotal(int $beratGram, int $hargaPerKg): int
    {
        return (int) round($beratGram * $hargaPerKg / 1000);
    }

    /**
     * Nomor bukti unik per hari: BSIL-260730-0001.
     *
     * PostgreSQL tidak mengizinkan FOR UPDATE pada hasil agregat MAX().
     * Karena semua pemanggil berada di dalam DB::transaction(), gunakan
     * transaction advisory lock per prefix di PostgreSQL lalu baca MAX()
     * tanpa FOR UPDATE. SQLite memakai transaction_mode=IMMEDIATE pada
     * konfigurasi koneksi, sehingga transaksi penulis sudah terserialisasi.
     */
    public static function nomorBuktiBerikutnya(\DateTimeInterface $tanggal): string
    {
        $prefix = 'BSIL-'.$tanggal->format('ymd').'-';

        if (DB::connection()->getDriverName() === 'pgsql') {
            // Lock hidup sampai transaksi pemanggil commit/rollback, termasuk saat tabel masih kosong.
            DB::selectOne('SELECT pg_advisory_xact_lock(hashtext(?))', [$prefix]);
        }

        $terakhir = static::where('nomor_bukti', 'like', $prefix.'%')
            ->max('nomor_bukti');

        $urut = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope periode — index-friendly.
     * Hindari whereDate() yang membungkus kolom dengan strftime() di SQLite
     * sehingga indeks tanggal_setor tidak terpakai (full SCAN).
     * Gunakan where('col', '>=', 'YYYY-MM-DD') langsung yang sargable.
     */
    public function scopeDalamPeriode(Builder $q, ?string $dari, ?string $sampai): Builder
    {
        if ($dari) {
            $q->where('tanggal_setor', '>=', $dari);
        }
        if ($sampai) {
            $q->where('tanggal_setor', '<=', $sampai);
        }

        return $q;
    }

    /**
     * Index-friendly filter user + periode dalam satu tempat.
     */
    public function scopeUntukUserPeriode(Builder $q, int $userId, ?string $dari, ?string $sampai): Builder
    {
        return $q->where('user_id', $userId)->dalamPeriode($dari, $sampai);
    }
}
