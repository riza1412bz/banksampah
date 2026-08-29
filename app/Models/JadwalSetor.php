<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'lokasi', 'keterangan'])]
class JadwalSetor extends Model
{
    protected $table = 'jadwal_setor';

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Jadwal tanpa user_id berlaku untuk semua nasabah. */
    public function untukSemua(): bool
    {
        return $this->user_id === null;
    }

    /** Jadwal hari ini atau setelahnya. */
    public function scopeMendatang(Builder $q): Builder
    {
        return $q->whereDate('tanggal', '>=', now()->toDateString());
    }

    /** Jadwal sebelum hari ini. */
    public function scopeSudahLewat(Builder $q): Builder
    {
        return $q->whereDate('tanggal', '<', now()->toDateString());
    }

    /**
     * Jadwal yang relevan untuk satu nasabah: jadwal umum + jadwal khusus dia.
     * Jadwal nasabah lain tidak boleh ikut terbawa.
     */
    public function scopeUntukNasabah(Builder $q, User $nasabah): Builder
    {
        return $q->where(fn ($w) => $w->whereNull('user_id')->orWhere('user_id', $nasabah->id));
    }

    /** Sudah lewat atau belum, dihitung dari tanggal hari ini. */
    public function sudahLewat(): bool
    {
        return $this->tanggal->lt(now()->startOfDay());
    }

    /** "08:00–11:00", "mulai 08:00", atau null kalau jam tidak diisi. */
    public function rentangJam(): ?string
    {
        if (! $this->jam_mulai) {
            return null;
        }

        $mulai = substr($this->jam_mulai, 0, 5);

        return $this->jam_selesai
            ? $mulai.'–'.substr($this->jam_selesai, 0, 5)
            : $mulai.' – selesai';
    }
}
