<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Nomor bukti unik per hari: BSIL-260730-0001
     *
     * Menggunakan SELECT ... FOR UPDATE di dalam transaksi pemanggil
     * untuk mencegah duplikasi pada request paralel.
     */
    public static function nomorBuktiBerikutnya(\DateTimeInterface $tanggal): string
    {
        $prefix = 'BSIL-'.$tanggal->format('ymd').'-';

        // MAX() + substr jauh lebih cepat daripada ORDER BY + limit untuk prefix scan,
        // dan lockForUpdate mencegah race ketika dipanggil di dalam DB::transaction.
        $terakhir = static::where('nomor_bukti', 'like', $prefix.'%')
            ->lockForUpdate()
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
