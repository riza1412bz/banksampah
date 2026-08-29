<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

#[Fillable(['kota', 'kecamatan', 'desa_kelurahan'])]
class Wilayah extends Model
{
    protected $table = 'wilayah';

    public $timestamps = false;

    /** Daftar kota/kabupaten untuk dropdown pertama. */
    public static function daftarKota(): Collection
    {
        return static::query()->distinct()->orderBy('kota')->pluck('kota');
    }

    /** Daftar kecamatan untuk satu kota. */
    public static function kecamatanUntuk(string $kota): Collection
    {
        return static::query()->where('kota', $kota)->distinct()->orderBy('kecamatan')->pluck('kecamatan');
    }

    /** Daftar desa/kelurahan untuk satu kota + kecamatan. */
    public static function desaUntuk(string $kota, string $kecamatan): Collection
    {
        return static::query()
            ->where('kota', $kota)
            ->where('kecamatan', $kecamatan)
            ->orderBy('desa_kelurahan')
            ->pluck('desa_kelurahan');
    }
}
