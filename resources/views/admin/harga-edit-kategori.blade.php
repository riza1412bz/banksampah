@extends('layouts.app')

@section('judul', ($k->exists ? 'Edit' : 'Tambah') . ' jenis sampah — Bank Sampah Indah Lestari')

@section('isi')
    <div class="mx-auto max-w-lg space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">
                {{ $k->exists ? 'Edit' : 'Tambah' }} jenis sampah
            </h1>
            @if ($k->exists)
                <p class="mt-1 text-sm text-zinc-500">{{ $k->kode ?: '-' }} — {{ $k->nama }}</p>
            @else
                <p class="mt-1 text-sm text-zinc-500">Daftarkan komoditas sampah baru dan tetapkan kelompok EPA WARM v16 serta harga aktifnya.</p>
            @endif
        </div>

        <form method="POST"
              action="{{ $k->exists ? route('admin.harga.update-kategori', $k) : route('admin.harga.store-kategori') }}"
              class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            @csrf
            @if ($k->exists)
                @method('PUT')
            @endif

            <div>
                <label for="nama" class="mb-1.5 block text-sm font-medium text-zinc-700">Nama jenis sampah</label>
                <input id="nama" name="nama" type="text" required maxlength="80"
                       value="{{ old('nama', $k->nama) }}"
                       placeholder="Contoh: Botol PET Bening, Kardus Box, Kaleng Susu"
                       class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm font-medium text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                @error('nama')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="kode" class="mb-1.5 block text-sm font-medium text-zinc-700">
                        Kode <span class="font-normal text-zinc-400">(opsional)</span>
                    </label>
                    <input id="kode" name="kode" type="text" maxlength="20"
                           value="{{ old('kode', $k->kode) }}"
                           placeholder="Contoh: P14, K01, ALM"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm font-mono font-medium text-zinc-900 uppercase focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    @error('kode')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="faktor_emisi_kg_co2e" class="mb-1.5 block text-sm font-medium text-zinc-700">
                        Faktor Emisi <span class="font-normal text-zinc-400">(kg CO₂e/kg)</span>
                    </label>
                    <input id="faktor_emisi_kg_co2e" name="faktor_emisi_kg_co2e" type="number" step="0.001" min="0" max="100"
                           value="{{ old('faktor_emisi_kg_co2e', $k->faktor_emisi_kg_co2e) }}"
                           placeholder="Otomatis dari EPA"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm font-medium tabular-nums text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    @error('faktor_emisi_kg_co2e')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="kelompok_sampah_id" class="block text-sm font-medium text-zinc-700">
                        Kelompok (Kategori EPA WARM v16)
                    </label>
                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200/60">61 Material</span>
                </div>
                <select id="kelompok_sampah_id" name="kelompok_sampah_id"
                        class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm font-medium text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    <option value="">— Tidak dipadankan / Custom —</option>
                    @foreach ($kelompok->groupBy('grup') as $grup => $items)
                        <optgroup label="{{ $grup }}">
                            @foreach ($items as $k2)
                                <option value="{{ $k2->id }}"
                                        data-ef="{{ $k2->ef_recycled }}"
                                        data-kode="{{ $k2->kode }}"
                                        @selected(old('kelompok_sampah_id', $k->kelompok_sampah_id) == $k2->id)>
                                    [{{ $k2->kode }}] {{ $k2->nama }} (EF: {{ number_format($k2->ef_recycled, 2, ',', '.') }} kg CO₂e/kg)
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-zinc-500">Memilih kelompok akan otomatis mengisi estimasi faktor emisi WARM v16.</p>
                @error('kelompok_sampah_id')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="harga_per_kg" class="mb-1.5 block text-sm font-medium text-zinc-700">
                    {{ $k->exists ? 'Harga per kg (Rp)' : 'Harga awal per kg (Rp)' }}
                    @if ($k->exists)
                        <span class="font-normal text-zinc-400">(ubah jika ingin menetapkan harga baru)</span>
                    @endif
                </label>
                <div class="flex items-center gap-2">
                    <span class="shrink-0 text-sm font-medium text-zinc-500">Rp</span>
                    <input id="harga_per_kg" name="harga_per_kg" type="number" min="1" step="1"
                           {{ $k->exists ? '' : 'required' }}
                           value="{{ old('harga_per_kg', $k->exists ? $k->hargaAktif()?->harga_per_kg : 1000) }}"
                           placeholder="{{ $k->exists ? ($k->hargaAktif()?->harga_per_kg ?? 'Belum ditetapkan') : 'Contoh: 2500' }}"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm font-semibold tabular-nums text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                </div>
                @if ($k->exists && $k->hargaAktif())
                    <p class="mt-1 text-xs text-zinc-500">Harga aktif saat ini: Rp {{ number_format($k->hargaAktif()->harga_per_kg, 0, ',', '.') }}/kg</p>
                @elseif (! $k->exists)
                    <p class="mt-1 text-xs text-zinc-500">Harga ini akan langsung aktif untuk pencatatan setoran & kalkulator.</p>
                @endif
                @error('harga_per_kg')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="keterangan" class="mb-1.5 block text-sm font-medium text-zinc-700">
                    Keterangan <span class="font-normal text-zinc-400">(opsional)</span>
                </label>
                <textarea id="keterangan" name="keterangan" rows="2"
                          placeholder="Catatan spesifikasi atau petunjuk pemilahan..."
                          class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">{{ old('keterangan', $k->keterangan) }}</textarea>
                @error('keterangan')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            @if ($k->exists)
                <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50/60 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-zinc-900">Status aktif</p>
                        <p class="text-xs text-zinc-500">Nonaktifkan jika jenis sampah ini sudah tidak diterima lagi.</p>
                    </div>
                    <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center">
                        <input type="checkbox" name="aktif" value="1"
                               {{ old('aktif', $k->aktif) ? 'checked' : '' }}
                               class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-zinc-300 transition peer-checked:bg-zinc-900"></span>
                        <span class="absolute left-0.5 inline-block size-5 rounded-full bg-white shadow-sm transition peer-checked:translate-x-5"></span>
                    </label>
                </div>
            @endif

            <button type="submit"
                    class="w-full rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 active:scale-[0.99] transition">
                {{ $k->exists ? 'Simpan perubahan' : 'Tambah jenis sampah' }}
            </button>
        </form>

        @if ($k->exists)
            <form method="POST" action="{{ route('admin.harga.destroy-kategori', $k) }}"
                  class="rounded-2xl border border-red-200 bg-red-50/50 p-5">
                @csrf
                @method('DELETE')
                <p class="text-sm font-semibold text-red-900">Hapus jenis sampah ini</p>
                <p class="mt-1 mb-4 text-xs text-red-700">Jika jenis sampah ini sudah pernah dipakai di transaksi setoran, data tidak akan dihapus fisik melainkan dinonaktifkan agar riwayat struk tetap utuh.</p>
                <button type="submit"
                        onclick="return confirm('Hapus jenis sampah {{ $k->nama }}?')"
                        class="w-full rounded-full border border-red-300 bg-white px-4 py-2.5 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 active:scale-[0.99] transition">
                    Hapus jenis sampah ini
                </button>
            </form>
        @endif

        <p class="text-center">
            <a href="{{ route('admin.harga.index') }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900 underline">
                ← Kembali ke daftar harga
            </a>
        </p>
    </div>

    <script>
        document.getElementById('kelompok_sampah_id')?.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const efInput = document.getElementById('faktor_emisi_kg_co2e');
            const kodeInput = document.getElementById('kode');
            if (opt && opt.dataset.ef && efInput && (!efInput.value || efInput.value === '0')) {
                efInput.value = opt.dataset.ef;
            }
            if (opt && opt.dataset.kode && kodeInput && !kodeInput.value) {
                kodeInput.value = opt.dataset.kode;
            }
        });
    </script>
@endsection
