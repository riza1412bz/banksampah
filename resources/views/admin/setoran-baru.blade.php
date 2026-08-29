@extends('layouts.app')

@section('judul', 'Catat setoran — Bank Sampah Indah Lestari')

@section('isi')
    <div class="mx-auto max-w-[640px] space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Catat setoran</h1>
            <p class="mt-1.5 text-sm leading-6 text-zinc-500">
                Centang jenis sampah yang dibawa. Harga yang dipakai adalah harga aktif hari ini, dan dibekukan di struk.
            </p>
        </div>

        @if ($nasabah->isEmpty())
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center shadow-sm">
                <p class="text-sm font-medium text-zinc-900">Belum ada nasabah aktif</p>
                <a href="{{ route('admin.nasabah.create') }}" class="mt-3 inline-flex rounded-full bg-zinc-900 px-5 py-2 text-sm font-medium text-white hover:bg-zinc-800">Tambah nasabah</a>
            </div>
        @else
            @php $tanpaHarga = $kategori->filter(fn ($k) => $k->harga_aktif <= 0 && $k->kode !== 'CAMPUR'); @endphp
            @if ($tanpaHarga->isNotEmpty())
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3.5">
                    <p class="text-sm font-medium text-amber-900">⚠️ Ada {{ $tanpaHarga->count() }} jenis sampah belum punya harga aktif</p>
                    <p class="mt-1 text-xs text-amber-700">{{ $tanpaHarga->pluck('nama')->join(', ') }}</p>
                    <a href="{{ route('admin.harga.index') }}" class="mt-2 inline-block text-xs font-medium text-zinc-900 underline">Atur harga →</a>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.setoran.store') }}"
                  class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                @csrf

                <div>
                    <label for="user_id" class="mb-1.5 block text-sm font-medium text-zinc-700">Nasabah</label>
                    <select id="user_id" name="user_id" required
                            class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm font-medium text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        @foreach ($nasabah as $n)
                            <option value="{{ $n->id }}" @selected(old('user_id') == $n->id)>
                                {{ $n->name }}@if ($n->kode_nasabah) — {{ $n->kode_nasabah }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <fieldset>
                    <legend class="mb-3 text-sm font-medium text-zinc-900">Jenis sampah yang disetor</legend>
                    @error('items')<p class="mb-3 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ $message }}</p>@enderror

                    <div class="space-y-2.5">
                    @foreach ($kategori as $k)
                        @php $punyaHarga = $k->harga_aktif > 0 || $k->kode === 'CAMPUR'; @endphp
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border {{ $punyaHarga ? 'border-zinc-200 bg-zinc-50/60' : 'border-red-200 bg-red-50/60 opacity-80' }} px-3.5 py-3">
                            <label class="flex items-center gap-3 min-w-0 {{ $punyaHarga ? 'cursor-pointer' : 'cursor-not-allowed' }}">
                                <input type="checkbox" name="items[{{ $k->id }}][checked]" value="1" data-id="{{ $k->id }}" data-kode="{{ $k->kode }}" data-harga="{{ $k->harga_aktif }}" data-ghg="{{ $k->dampak_per_kg['ghg_kg_co2e'] }}" data-kelompok="{{ $k->dampak_per_kg['kelompok_nama'] ?? '' }}" class="cek-item size-[18px] shrink-0 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 disabled:opacity-40" @disabled(! $punyaHarga) @checked(old("items.{$k->id}.checked"))>
                                <span class="text-sm font-medium text-zinc-900">{{ $k->nama }}</span>
                            </label>
                            <span class="shrink-0 text-sm font-semibold tabular-nums text-zinc-900">
                                @if ($k->harga_aktif > 0)
                                    Rp {{ number_format($k->harga_aktif, 0, ',', '.') }}/kg
                                @elseif ($k->kode === 'CAMPUR')
                                    <span class="text-xs font-normal text-zinc-500">Harga di bawah</span>
                                @else
                                    <span class="text-xs font-medium text-red-600">Belum ada harga</span>
                                @endif
                            </span>
                            <div class="flex w-full items-center gap-2 pl-7">
                                <input type="number" name="items[{{ $k->id }}][berat_kg]" inputmode="decimal" step="0.1" min="0" placeholder="0 kg" value="{{ old("items.{$k->id}.berat_kg") }}" class="berat-item w-28 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium tabular-nums focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 disabled:opacity-30" disabled>
                                @if ($k->kode === 'CAMPUR')
                                    <span class="text-xs text-zinc-500">harga di bawah</span>
                                @elseif (! $punyaHarga)
                                    <a href="{{ route('admin.harga.index') }}" class="text-xs font-medium text-zinc-900 underline">atur harga</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    </div>
                </fieldset>

                <div id="panel-campur" class="hidden rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-4 space-y-3">
                    <p class="text-sm font-medium text-zinc-900">Harga untuk Campur</p>
                    <div>
                        <label for="campur_harga" class="mb-1 block text-xs text-zinc-500">Harga per kg (custom)</label>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-zinc-500">Rp</span>
                            <input id="campur_harga" name="campur_harga" type="number" min="1" step="1" value="{{ old('campur_harga', 1500) }}" class="w-32 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium tabular-nums focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        </div>
                    </div>
                </div>

                <div aria-live="polite" class="rounded-2xl bg-zinc-900 px-6 py-5 text-center text-white">
                    <p class="text-xs font-medium uppercase tracking-widest text-white/60">Total dibayarkan</p>
                    <p id="pratinjau" class="mt-1 text-3xl font-semibold tracking-tight">Rp 0</p>
                    <p class="mt-1 text-xs text-white/60">Total berat <span id="pratinjau-berat" class="font-medium text-white">0 kg</span></p>
                </div>

                <div aria-live="polite" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Dampak lingkungan setoran ini</p>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-700 border border-emerald-200/60">EPA WARM v16</span>
                    </div>
                    <p class="mt-3 rounded-xl bg-zinc-50 px-3 py-2.5 text-xs leading-relaxed text-zinc-600">
                        <span class="font-medium text-zinc-900">Rumus:</span> E<sub>terhindar</sub> = Σ (berat × faktor emisi)<br>
                        <span class="text-zinc-500">Konversi: 1 pohon ≈ 22,9 kg CO₂e · 1 kg CO₂e ≈ 4,0 km mobil · 1 kWh ≈ 100 jam LED 10W</span>
                    </p>
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-xl bg-zinc-50 p-3">
                            <p class="text-[11px] font-medium text-zinc-500">kg CO₂e dihemat</p>
                            <p id="dampak-ghg" class="mt-1 text-lg font-bold text-zinc-900 tabular-nums">0</p>
                        </div>
                        <div class="rounded-xl bg-zinc-50 p-3">
                            <p class="text-[11px] font-medium text-zinc-500">🌳 Bibit pohon</p>
                            <p id="dampak-pohon" class="mt-1 text-lg font-bold text-emerald-700 tabular-nums">0</p>
                        </div>
                        <div class="rounded-xl bg-zinc-50 p-3">
                            <p class="text-[11px] font-medium text-zinc-500">🚗 Mobil bensin</p>
                            <p id="dampak-mobil" class="mt-1 text-lg font-bold text-zinc-900 tabular-nums">0 KM</p>
                        </div>
                        <div class="rounded-xl bg-zinc-50 p-3">
                            <p class="text-[11px] font-medium text-zinc-500">💡 Lampu LED 10W</p>
                            <p id="dampak-lampu" class="mt-1 text-lg font-bold text-amber-600 tabular-nums">0 Jam</p>
                        </div>
                    </div>
                    <ul id="dampak-rincian" class="mt-4 space-y-1.5 border-t border-zinc-100 pt-3 text-xs"></ul>
                    <p class="mt-3 text-[11px] leading-relaxed text-zinc-400">Metodologi EPA WARM v16 & EPR Carbon Impact Analytics · Relatable Metrics.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="tanggal_setor" class="mb-1.5 block text-sm font-medium text-zinc-700">Tanggal <span class="font-normal text-zinc-400">(hari ini jika kosong)</span></label>
                        <input id="tanggal_setor" name="tanggal_setor" type="date" value="{{ old('tanggal_setor') }}" class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        @error('tanggal_setor')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="catatan" class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan <span class="font-normal text-zinc-400">(opsional)</span></label>
                        <textarea id="catatan" name="catatan" rows="1" class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">{{ old('catatan') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 active:scale-[0.99] transition">
                    Simpan & buka struk
                </button>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
    @vite('resources/js/pages/setoran-baru.js')
@endpush
