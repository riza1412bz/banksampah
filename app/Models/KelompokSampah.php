<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kelompok sampah = kategori material EPA ReCon Tool. Menyimpan faktor
 * dampak lingkungan per kelompok (GHG dalam MTCO2e/ton dan energi dalam
 * MMBtu/ton) yang dipakai kalkulator dampak lingkungan nanti.
 */
#[Fillable([
    'kode',
    'nama',
    'deskripsi',
    'urutan',
    'default_recycled_content',
    'ef_virgin',
    'ef_recycled',
    'ef_current',
    'forest_c_seq',
    'energy_virgin',
    'energy_recycled',
    'energy_current',
    'landfilling_ef',
    'combustion_ef',
    'energy_landfilling_ef',
    'energy_combustion_ef',
    'loss_rate',
    'aktif',
])]
class KelompokSampah extends Model
{
    protected $table = 'kelompok_sampah';

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function kategori(): HasMany
    {
        return $this->hasMany(KategoriSampah::class, 'kelompok_sampah_id');
    }
}
