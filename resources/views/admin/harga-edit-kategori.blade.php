@extends('layouts.app')

@section('judul', 'Edit jenis plastik — Bank Sampah Indah Lestari')

@section('isi')
    <div class="mx-auto max-w-md space-y-5">
        <div>
            <h1 class="font-display text-2xl font-bold text-terpal">
                {{ $k->exists ? 'Edit' : 'Tambah' }} jenis plastik
            </h1>
            @if ($k->exists)
                <p class="mt-1 text-sm text-karet/60">{{ $k->kode }} — {{ $k->nama }}</p>
            @endif
        </div>

        <form method="POST"
              action="{{ $k->exists ? route('admin.harga.update-kategori', $k) : route('admin.harga.store-kategori') }}"
              class="rounded-3xl border-2 border-karet/15 bg-karung/70 p-5 space-y-4">
            @csrf
            @if ($k->exists)
                @method('PUT')
            @endif

            <div>
                <label for="nama" class="mb-1.5 block text-sm font-semibold text-karet">Nama plastik</label>
                <input id="nama" name="nama" type="text" required maxlength="80"
                       value="{{ old('nama', $k->nama) }}"
                       class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
                @error('nama')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="kode" class="mb-1.5 block text-sm font-semibold text-karet">
                    Kode <span class="font-normal text-karet/45">(opsional, untuk referensi)</span>
                </label>
                <input id="kode" name="kode" type="text" maxlength="20"
                       value="{{ old('kode', $k->kode) }}"
                       class="angka w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
                @error('kode')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="keterangan" class="mb-1.5 block text-sm font-semibold text-karet">Keterangan</label>
                <textarea id="keterangan" name="keterangan" rows="2"
                          class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">{{ old('keterangan', $k->keterangan) }}</textarea>
                @error('keterangan')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="kelompok_sampah_id" class="mb-1.5 block text-sm font-semibold text-karet">
                    Kelompok (kategori EPA)
                    <span class="font-normal text-karet/45">(opsional)</span>
                </label>
                <select id="kelompok_sampah_id" name="kelompok_sampah_id"
                        class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
                    <option value="">—</option>
                    @foreach ($kelompok as $k2)
                        <option value="{{ $k2->id }}"
                                @selected(old('kelompok_sampah_id', $k->kelompok_sampah_id) == $k2->id)>
                            {{ $k2->nama }}
                        </option>
                    @endforeach
                </select>
                @error('kelompok_sampah_id')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="harga_per_kg" class="mb-1.5 block text-sm font-semibold text-karet">
                    {{ $k->exists ? 'Harga per kg (Rp)' : 'Harga awal per kg (Rp)' }}
                    @if ($k->exists)
                        <span class="font-normal text-karet/45">(opsional, ubah jika ingin memperbarui)</span>
                    @endif
                </label>
                <div class="flex items-center gap-2">
                    <span class="shrink-0 text-sm font-bold text-karet/60">Rp</span>
                    <input id="harga_per_kg" name="harga_per_kg" type="number" min="1" step="1"
                           {{ $k->exists ? '' : 'required' }}
                           value="{{ old('harga_per_kg', $k->exists ? $k->hargaAktif()?->harga_per_kg : 1000) }}"
                           placeholder="{{ $k->exists ? ($k->hargaAktif()?->harga_per_kg ?? 'Belum ditetapkan') : 'Contoh: 2500' }}"
                           class="angka w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 font-bold text-karet focus:border-terpal focus:outline-none">
                </div>
                @if ($k->exists && $k->hargaAktif())
                    <p class="mt-1 text-xs text-karet/55">Harga aktif saat ini: Rp {{ number_format($k->hargaAktif()->harga_per_kg, 0, ',', '.') }}/kg</p>
                @elseif (! $k->exists)
                    <p class="mt-1 text-xs text-karet/55">Harga ini akan langsung aktif untuk pencatatan setoran & kalkulator.</p>
                @endif
                @error('harga_per_kg')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
            </div>

            @if ($k->exists)
                <div class="flex items-center justify-between rounded-2xl border-2 border-karet/10 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-karet">Status aktif</p>
                        <p class="text-xs text-karet/55">Nonaktifkan kalau jenis ini tidak dipakai lagi.</p>
                    </div>
                    <label class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer items-center">
                        <input type="checkbox" name="aktif" value="1"
                               {{ old('aktif', $k->aktif) ? 'checked' : '' }}
                               class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-karet/25 transition peer-checked:bg-terpal"></span>
                        <span class="absolute left-1 inline-block size-5 rounded-full bg-white shadow-sm transition peer-checked:translate-x-5"></span>
                    </label>
                </div>
            @endif

            <button type="submit"
                    class="w-full rounded-xl border-2 border-terpal bg-terpal px-4 py-3 font-semibold text-karung transition hover:bg-terpal-muda focus:outline-none">
                {{ $k->exists ? 'Simpan perubahan' : 'Tambah jenis plastik' }}
            </button>
        </form>

        @if ($k->exists)
            <form method="POST" action="{{ route('admin.harga.destroy-kategori', $k) }}"
                  class="rounded-3xl border-2 border-timbangan/30 bg-timbangan/5 p-5">
                @csrf
                @method('DELETE')
                <p class="mb-3 text-sm font-semibold text-timbangan">Hapus jenis plastik ini</p>
                <p class="mb-4 text-xs text-karet/55">Kalau jenis ini sudah pernah dipakai setoran, data tidak dihapus — hanya dinonaktifkan supaya riwayat struk tetap utuh.</p>
                <button type="submit"
                        onclick="return confirm('Hapus jenis plastik {{ $k->nama }}?')"
                        class="w-full rounded-xl border-2 border-timbangan/50 px-4 py-3 font-semibold text-timbangan transition hover:bg-timbangan/10 focus:outline-none">
                    Hapus jenis plastik ini
                </button>
            </form>
        @endif

        <p class="text-center">
            <a href="{{ route('admin.harga.index') }}" class="text-sm font-semibold text-karet/60 underline decoration-2 underline-offset-2 hover:text-terpal">
                ← Kembali ke daftar harga
            </a>
        </p>
    </div>
@endsection
