<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'email', 'password', 'role', 'jenis_nasabah', 'kode_nasabah', 'telepon', 'alamat', 'nik', 'kota', 'kecamatan', 'desa_kelurahan', 'jalan', 'rt_rw', 'detail_rumah', 'aktif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_NASABAH = 'nasabah';

    public const JENIS_PERORANGAN = 'perorangan';
    public const JENIS_CORPORATE = 'corporate';
    public const JENIS_NASABAH_OPTIONS = [self::JENIS_PERORANGAN, self::JENIS_CORPORATE];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
            'jenis_nasabah' => 'string',
        ];
    }

    public function isPerorangan(): bool
    {
        return $this->jenis_nasabah === self::JENIS_PERORANGAN;
    }

    public function isCorporate(): bool
    {
        return $this->jenis_nasabah === self::JENIS_CORPORATE;
    }

    public function prefixKode(): string
    {
        return $this->isCorporate() ? 'CORP' : 'BSIL';
    }

    public static function prefixUntukJenis(string $jenis): string
    {
        return $jenis === self::JENIS_CORPORATE ? 'CORP' : 'BSIL';
    }

    /**
     * Buat nasabah baru dengan kode_nasabah yang race-safe.
     *
     * Generasi kode + INSERT dilakukan dalam SATU transaksi supaya lock (Postgres)
     * / write-lock IMMEDIATE (SQLite) menahan sampai commit — mencegah dua request
     * paralel menghitung kode yang sama. Dipakai baik oleh registrasi mandiri
     * maupun admin, jadi tidak ada jalur yang tertinggal tanpa proteksi.
     *
     * @param  array<string, mixed>  $atribut  data tervalidasi (tanpa role/jenis/kode/aktif)
     */
    public static function buatNasabah(array $atribut, string $jenis = self::JENIS_PERORANGAN): self
    {
        return DB::transaction(function () use ($atribut, $jenis) {
            return static::create([
                ...$atribut,
                'role' => self::ROLE_NASABAH,
                'jenis_nasabah' => $jenis,
                'kode_nasabah' => self::kodeNasabahTerkunci($jenis),
                'aktif' => true,
            ]);
        });
    }

    /**
     * Nomor urut kode berikutnya untuk satu prefix, dengan lockForUpdate.
     * WAJIB dipanggil di dalam transaksi (lihat buatNasabah).
     *
     * ponytail: untuk nasabah PERTAMA sebuah prefix (tabel kosong) FOR UPDATE tak punya
     * baris untuk dikunci, jadi backstop-nya adalah UNIQUE index kode_nasabah. Batas urut 9999/prefix.
     */
    protected static function kodeNasabahTerkunci(string $jenis): string
    {
        $prefix = self::prefixUntukJenis($jenis);

        $terakhir = static::where('kode_nasabah', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->orderByDesc('kode_nasabah')
            ->value('kode_nasabah');

        $urut = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }

    public function labelJenis(): string
    {
        return $this->isCorporate() ? 'Corporate' : 'Perorangan';
    }

    public function setoran(): HasMany
    {
        return $this->hasMany(Setoran::class)->latest('tanggal_setor');
    }

    public function jadwalSetor(): HasMany
    {
        return $this->hasMany(JadwalSetor::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Total berat yang pernah disetor, dalam gram. — 1 query agregat */
    public function totalBeratGram(): int
    {
        return (int) $this->setoran()->sum('berat_gram');
    }

    /** Total tabungan rupiah dari seluruh setoran. — 1 query agregat */
    public function totalRupiah(): int
    {
        return (int) $this->setoran()->sum('total_rupiah');
    }

    /** Sekali ambil kedua total agar tidak 2 roundtrip */
    public function totalAgregat(): array
    {
        $row = $this->setoran()->selectRaw('SUM(berat_gram) as b, SUM(total_rupiah) as r')->first();

        return [
            'berat_gram' => (int) ($row->b ?? 0),
            'rupiah' => (int) ($row->r ?? 0),
        ];
    }

    /**
     * Alamat terstruktur lengkap (jalan, RT/RW, detail, desa, kecamatan, kota).
     * Null kalau belum ada data alamat terstruktur sama sekali.
     */
    public function alamatLengkap(): ?string
    {
        $bagian = array_values(array_filter([
            $this->jalan,
            $this->rt_rw,
            $this->detail_rumah,
            $this->desa_kelurahan,
            $this->kecamatan,
            $this->kota,
        ], fn ($v) => $v !== null && $v !== ''));

        return $bagian === [] ? null : implode(', ', $bagian);
    }
}
