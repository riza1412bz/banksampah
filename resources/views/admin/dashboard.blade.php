@extends('layouts.app')

@section('judul', 'Dashboard — Bank Sampah Indah Lestari')

@section('isi')
    <div class="space-y-8">

        {{-- Header --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-[28px] font-semibold tracking-tight text-zinc-900 leading-none">Dashboard</h1>
                <p class="text-sm text-zinc-500">{{ $totalNasabah }} nasabah aktif · Ringkasan periode terpilih</p>
            </div>
            <a href="{{ route('admin.setoran.create') }}"
               class="inline-flex items-center gap-2 rounded-full bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-zinc-800 active:scale-[0.98]">
                <svg aria-hidden="true" viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Catat setoran
            </a>
        </div>

        {{-- Filter periode — clean white card --}}
        <form method="GET" action="{{ route('admin.dashboard') }}"
              class="flex flex-wrap items-end gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:gap-4">
            <div class="flex-1 min-w-[140px]">
                <label for="dari" class="mb-1.5 block text-xs font-medium tracking-wide text-zinc-500">Dari</label>
                <input id="dari" name="dari" type="date" value="{{ $dari }}"
                       class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
            </div>
            <div class="flex-1 min-w-[140px]">
                <label for="sampai" class="mb-1.5 block text-xs font-medium tracking-wide text-zinc-500">Sampai</label>
                <input id="sampai" name="sampai" type="date" value="{{ $sampai }}"
                       class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
            </div>
            <button type="submit"
                    class="h-[42px] rounded-full bg-zinc-900 px-6 text-sm font-medium text-white transition hover:bg-zinc-800 active:scale-[0.98]">
                Terapkan
            </button>
            <div class="ml-auto flex flex-wrap items-center gap-1.5">
                <span class="mr-1 hidden text-xs text-zinc-400 sm:inline">Cepat:</span>
                <a href="{{ route('admin.dashboard', ['dari' => now()->toDateString(), 'sampai' => now()->toDateString()]) }}" class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 hover:border-zinc-900 hover:text-zinc-900">Hari ini</a>
                <a href="{{ route('admin.dashboard', ['dari' => now()->startOfWeek()->toDateString(), 'sampai' => now()->toDateString()]) }}" class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 hover:border-zinc-900 hover:text-zinc-900">Minggu ini</a>
                <a href="{{ route('admin.dashboard', ['dari' => now()->startOfMonth()->toDateString(), 'sampai' => now()->toDateString()]) }}" class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 hover:border-zinc-900 hover:text-zinc-900">Bulan ini</a>
            </div>
        </form>

        {{-- Statistik 4 kartu — clean, no heavy border --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Sampah terkumpul', 'nilai' => number_format($rekap['berat_gram'] / 1000, 1, ',', '.').' kg', 'sub' => 'Total berat'],
                ['label' => 'Dibayarkan', 'nilai' => 'Rp '.number_format($rekap['rupiah'], 0, ',', '.'), 'sub' => 'Ke nasabah'],
                ['label' => 'Transaksi', 'nilai' => number_format($rekap['transaksi'], 0, ',', '.'), 'sub' => 'Kali setor'],
                ['label' => 'Nasabah setor', 'nilai' => number_format($rekap['nasabah_aktif'], 0, ',', '.'), 'sub' => 'Aktif periode'],
            ] as $kartu)
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-widest text-zinc-400">{{ $kartu['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold tracking-tight text-zinc-900">{{ $kartu['nilai'] }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ $kartu['sub'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Dua kolom: per kategori + terbaru --}}
        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">

            <section aria-labelledby="judul-kategori" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-baseline justify-between">
                    <div>
                        <h2 id="judul-kategori" class="text-base font-semibold tracking-tight text-zinc-900">Per jenis plastik</h2>
                        <p class="mt-1 text-xs text-zinc-500">Dalam periode terpilih</p>
                    </div>
                    <span class="rounded-full bg-zinc-900 px-2.5 py-1 text-xs font-medium text-white">{{ $perKategori->count() }} jenis</span>
                </div>

                @if ($perKategori->isEmpty())
                    <div class="rounded-xl border border-dashed border-zinc-200 bg-zinc-50 py-10 text-center">
                        <p class="text-sm text-zinc-500">Belum ada setoran di periode ini</p>
                        <p class="mt-1 text-xs text-zinc-400">Ubah rentang tanggal di atas</p>
                    </div>
                @else
                    @php $maks = $perKategori->max('rupiah') ?: 1; @endphp
                    <ul class="space-y-4">
                        @foreach ($perKategori as $baris)
                            <li>
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="text-sm font-medium text-zinc-900">{{ $baris->kategori->nama ?? '—' }}</span>
                                    <span class="shrink-0 text-sm font-semibold tabular-nums text-zinc-900">Rp {{ number_format($baris->rupiah, 0, ',', '.') }}</span>
                                </div>
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100">
                                    <div class="h-full rounded-full bg-zinc-900" style="width: {{ round($baris->rupiah / $maks * 100, 1) }}%"></div>
                                </div>
                                <p class="mt-1.5 flex items-center gap-2 text-xs text-zinc-500">
                                    <span class="tabular-nums">{{ number_format($baris->berat_gram / 1000, 1, ',', '.') }} kg</span>
                                    <span class="size-1 rounded-full bg-zinc-300"></span>
                                    <span>{{ $baris->jumlah }} kali</span>
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section aria-labelledby="judul-terbaru" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 id="judul-terbaru" class="text-base font-semibold tracking-tight text-zinc-900">Setoran terbaru</h2>
                    <p class="mt-1 text-xs text-zinc-500">{{ $terbaru->count() }} transaksi terakhir</p>
                </div>

                @if ($terbaru->isEmpty())
                    <div class="rounded-xl border border-dashed border-zinc-200 bg-zinc-50 py-10 text-center">
                        <p class="text-sm text-zinc-500">Belum ada transaksi</p>
                    </div>
                @else
                    <ul class="divide-y divide-zinc-100 -mx-6">
                        @foreach ($terbaru as $item)
                            <li class="flex items-center gap-4 px-6 py-3.5 hover:bg-zinc-50/70 transition">
                                <div class="grid size-9 shrink-0 place-items-center rounded-full bg-zinc-900 text-xs font-semibold text-white">
                                    {{ strtoupper(substr($item->user->name,0,1)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-900">{{ $item->user->name }}</p>
                                    <p class="truncate text-xs text-zinc-500">
                                        {{ $item->kategori?->nama ?? '—' }} · {{ number_format($item->berat_kg, 1, ',', '.') }} kg · {{ $item->tanggal_setor->translatedFormat('j M') }}
                                    </p>
                                </div>
                                <span class="shrink-0 text-sm font-semibold tabular-nums text-zinc-900">Rp {{ number_format($item->total_rupiah, 0, ',', '.') }}</span>
                                <a href="{{ route('nasabah.struk', $item) }}" class="shrink-0 rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 hover:border-zinc-900 hover:bg-zinc-900 hover:text-white transition">Struk</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

        </div>
    </div>
@endsection
