<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kategori_sampah_id', 'harga_per_kg', 'berlaku_dari', 'berlaku_sampai', 'dibuat_oleh'])]
class HargaSampah extends Model
{
    protected $table = 'harga_sampah';

    protected function casts(): array
    {
        return [
            'berlaku_dari' => 'date',
            'berlaku_sampai' => 'date',
            'harga_per_kg' => 'integer',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSampah::class, 'kategori_sampah_id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function masihBerlaku(): bool
    {
        return $this->berlaku_sampai === null;
    }
}
