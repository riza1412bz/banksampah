@extends('layouts.app')

@section('judul', 'Hitung sampah — Bank Sampah Indah Lestari')

@section('isi')
    <div class="mx-auto max-w-[640px] space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Hitung dulu</h1>
            <p class="mt-1.5 text-sm leading-6 text-zinc-500">
                Centang jenis plastikmu, isi berat masing-masing — totalnya muncul langsung. Angka final ditimbang petugas.
            </p>
        </div>

        {{-- Grafik harga — Area Smooth Line + Tooltip/Crosshair + Filter + Dropdown per kategori --}}
        <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold tracking-tight text-zinc-900">Grafik harga</h2>
                    <p class="mt-1 text-xs text-zinc-500">Harga per kg dari waktu ke waktu — pilih kategori & rentang.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <label class="sr-only" for="chart-kategori-1">Kategori 1</label>
                    <select id="chart-kategori-1" class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 focus:border-zinc-900 focus:outline-none">
                        @foreach ($kategoriOptions as $opt)
                            <option value="{{ $opt['id'] }}" @selected($loop->first)>{{ $opt['nama'] }} ({{ $opt['kode'] }})</option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="chart-kategori-2">Kategori 2</label>
                    <select id="chart-kategori-2" class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-500 focus:border-zinc-900 focus:outline-none">
                        <option value="">— Bandingkan —</option>
                        @foreach ($kategoriOptions as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['nama'] }} ({{ $opt['kode'] }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="relative mt-5 h-[260px] sm:h-[300px]">
                <canvas id="chart-harga" class="h-full w-full"></canvas>
            </div>

            {{-- Segmented control waktu --}}
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-4">
                <div class="flex rounded-full border border-zinc-200 bg-zinc-50 p-1 text-xs" role="group" aria-label="Filter waktu">
                    <button type="button" data-range="7" class="chart-range rounded-full px-3 py-1.5 font-medium transition bg-zinc-900 text-white">1 Minggu</button>
                    <button type="button" data-range="30" class="chart-range rounded-full px-3 py-1.5 font-medium text-zinc-600 hover:text-zinc-900">1 Bulan</button>
                    <button type="button" data-range="90" class="chart-range rounded-full px-3 py-1.5 font-medium text-zinc-600 hover:text-zinc-900">3 Bulan</button>
                    <button type="button" data-range="180" class="chart-range rounded-full px-3 py-1.5 font-medium text-zinc-600 hover:text-zinc-900">6 Bulan</button>
                    <button type="button" data-range="365" class="chart-range rounded-full px-3 py-1.5 font-medium text-zinc-600 hover:text-zinc-900">1 Tahun</button>
                    <button type="button" data-range="0" class="chart-range rounded-full px-3 py-1.5 font-medium text-zinc-600 hover:text-zinc-900">Semua</button>
                </div>
                <span id="chart-info" class="text-xs tabular-nums text-zinc-400"></span>
            </div>

            <script id="chart-data" type="application/json">{!! json_encode($chartData, JSON_UNESCAPED_UNICODE) !!}</script>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-900">Hitung manual</h2>
                <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-500">{{ $kategori->count() }} jenis</span>
            </div>
            <ul class="divide-y divide-zinc-100">
                @foreach ($kategori as $k)
                    <li class="py-3.5" data-item data-harga="{{ $k->harga_aktif }}">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input type="checkbox" data-checklist value="{{ $k->id }}" class="size-4 shrink-0 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900" {{ $loop->first ? 'checked' : '' }}>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-zinc-900">{{ $k->nama }}</span>
                                <span class="block text-xs tabular-nums text-zinc-500">Rp {{ number_format($k->harga_aktif, 0, ',', '.') }}/kg</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <input type="number" inputmode="decimal" min="0" step="0.1" value="{{ $loop->first ? '1' : '' }}" data-berat placeholder="0"
                                       class="w-20 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-right text-sm font-medium tabular-nums focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                                <span class="text-xs font-medium text-zinc-400">kg</span>
                            </span>
                        </label>
                        <p class="mt-1.5 pl-7 text-xs font-medium tabular-nums text-zinc-900" data-subtotal>—</p>
                    </li>
                @endforeach
            </ul>
        </section>

        <div aria-live="polite" class="rounded-2xl bg-zinc-900 p-6 text-white shadow-sm">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-widest text-white/60">Total perkiraan</p>
                    <p id="hasil" class="mt-1 text-3xl font-semibold tracking-tight">Rp 0</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-medium uppercase tracking-widest text-white/60">Total berat</p>
                    <p id="total-berat" class="mt-1 text-sm font-medium tabular-nums">0 kg</p>
                </div>
            </div>
            <div class="mt-5 flex items-center justify-between border-t border-white/10 pt-4">
                <p class="text-xs text-white/60">Harga hari ini, bisa berubah saat setor.</p>
                <button type="button" id="reset" class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-xs font-medium text-zinc-900 shadow-sm hover:bg-zinc-100 active:scale-[0.98] transition">
                    <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    Reset
                </button>
            </div>
        </div>

        <p class="text-center text-xs text-zinc-500">
            Sudah pernah setor? <a href="{{ route('nasabah.beranda') }}" class="font-medium text-zinc-900 underline decoration-zinc-300 underline-offset-4 hover:decoration-zinc-900">Lihat riwayat</a>
        </p>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/pages/kalkulator.js')
@endpush
