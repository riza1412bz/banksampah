@extends('layouts.app')

@section('judul', 'Setoran — Bank Sampah Indah Lestari')

@section('isi')
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Setoran</h1>
                <p class="mt-1 text-sm text-zinc-500">Semua transaksi sesuai periode</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.setoran.create') }}" class="inline-flex items-center gap-2 rounded-full bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-zinc-800">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Catat
                </a>
                <a id="link-export" href="{{ route('admin.setoran.export', ['dari' => $dari, 'sampai' => $sampai, 'cari' => $cari ?? '']) }}" class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-zinc-700 hover:border-zinc-900 hover:text-zinc-900">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                    Export
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.setoran.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex-1 min-w-[130px]">
                <label for="dari" class="mb-1.5 block text-xs font-medium text-zinc-500">Dari</label>
                <input id="dari" name="dari" type="date" value="{{ $dari }}" class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
            </div>
            <div class="flex-1 min-w-[130px]">
                <label for="sampai" class="mb-1.5 block text-xs font-medium text-zinc-500">Sampai</label>
                <input id="sampai" name="sampai" type="date" value="{{ $sampai }}" class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
            </div>
            <button type="submit" class="h-[42px] rounded-full bg-zinc-900 px-6 text-sm font-medium text-white hover:bg-zinc-800">Terapkan</button>
            <div class="ml-auto flex gap-1.5">
                <a href="{{ route('admin.setoran.index', ['dari' => now()->toDateString(), 'sampai' => now()->toDateString()]) }}" class="rounded-full border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:border-zinc-900">Hari ini</a>
                <a href="{{ route('admin.setoran.index', ['dari' => now()->startOfWeek()->toDateString(), 'sampai' => now()->toDateString()]) }}" class="rounded-full border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:border-zinc-900">Minggu ini</a>
                <a href="{{ route('admin.setoran.index', ['dari' => now()->startOfMonth()->toDateString(), 'sampai' => now()->toDateString()]) }}" class="rounded-full border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:border-zinc-900">Bulan ini</a>
            </div>
        </form>

        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <label for="cari-nasabah" class="mb-1.5 block text-xs font-medium text-zinc-500">Cari nasabah</label>
            <div class="relative max-w-sm">
                <input id="cari-nasabah" type="text" autocomplete="off" value="{{ $cari ?? '' }}" placeholder="Ketik nama nasabah…" class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 pr-9 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                <button type="button" id="cari-bersih" aria-label="Hapus pencarian" class="absolute inset-y-0 right-0 hidden items-center px-3 text-zinc-400 hover:text-red-500">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
                <ul id="cari-saran" role="listbox" class="absolute z-20 mt-2 hidden max-h-60 w-full overflow-auto rounded-xl border border-zinc-200 bg-white py-1 text-sm shadow-lg"></ul>
            </div>
        </div>

        @if ($transaksi->isEmpty())
            <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 py-12 text-center">
                <p class="text-sm font-medium text-zinc-900">Belum ada setoran di periode ini.</p>
                <p class="mt-1 text-xs text-zinc-500">Ubah rentang tanggal untuk melihat lain.</p>
            </div>
        @else
            <ul data-list="transaksi" class="space-y-3">
                @foreach ($transaksi as $t)
                    <li data-nama="{{ $t->nama }}" class="group flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm hover:border-zinc-300 hover:shadow transition">
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-widest text-zinc-400">{{ $t->nomor_bukti }} · {{ $t->tanggal->translatedFormat('j M Y') }}</p>
                            <p class="mt-1 truncate text-sm font-medium text-zinc-900">{{ $t->nama }}</p>
                            <p class="truncate text-xs text-zinc-500">{{ $t->jenis }} · {{ $t->jumlah_item }} item · {{ number_format($t->berat_kg, 1, ',', '.') }} kg</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-semibold tabular-nums text-zinc-900">Rp {{ number_format($t->rupiah, 0, ',', '.') }}</p>
                            <a href="{{ route('nasabah.struk', $t->id) }}" class="mt-1 inline-flex rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 group-hover:border-zinc-900 group-hover:bg-zinc-900 group-hover:text-white">Struk</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

@endsection

@push('scripts')
    @vite('resources/js/pages/setoran-index.js')
@endpush
