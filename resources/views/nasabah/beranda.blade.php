@extends('layouts.app')

@section('judul', 'Beranda — Bank Sampah Indah Lestari')

@section('isi')
    <div class="mx-auto max-w-[640px] space-y-6">
        {{-- Greeting — minimal --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-widest text-zinc-400">Selamat datang</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900">{{ $nasabah->name }}</h1>
            <p class="mt-1 inline-flex items-center gap-2 text-xs text-zinc-500">
                <span class="rounded-full bg-zinc-900 px-2.5 py-1 font-mono text-xs font-medium text-white">{{ $nasabah->kode_nasabah ?? 'Nasabah' }}</span>
                <span>{{ $nasabah->email }}</span>
            </p>
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-widest text-zinc-400">Tabungan{{ $dari || $sampai ? ' · periode' : '' }}</p>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-zinc-900">Rp {{ number_format($totalRupiah, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-zinc-500">Saldo tersedia</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-widest text-zinc-400">Berat{{ $dari || $sampai ? ' · periode' : '' }}</p>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-zinc-900">{{ number_format($totalBeratGram / 1000, 1, ',', '.') }} kg</p>
                <p class="mt-1 text-xs text-zinc-500">Sampah terkumpul</p>
            </div>
        </div>

        @if ($jadwalBerikutnya)
            <div class="flex items-center gap-4 rounded-2xl border border-zinc-200 bg-zinc-900 px-5 py-4 text-white shadow-sm">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-white/10">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="white" stroke-width="1.8"><path d="M8 2v3M16 2v3"/><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-widest text-white/60">Jadwal terdekat</p>
                    <p class="mt-0.5 text-sm font-medium">{{ $jadwalBerikutnya->tanggal->format('l, d M Y') }} · {{ $jadwalBerikutnya->jam_mulai_label }}</p>
                    <p class="text-xs text-white/60 truncate">{{ $jadwalBerikutnya->lokasi }}</p>
                </div>
                <a href="{{ route('nasabah.jadwal') }}" class="ml-auto shrink-0 rounded-full bg-white px-4 py-1.5 text-xs font-medium text-zinc-900 hover:bg-zinc-100">Lihat</a>
            </div>
        @endif

        {{-- Riwayat --}}
        <section>
            <div class="mb-3 flex items-baseline justify-between">
                <h2 class="text-base font-semibold tracking-tight text-zinc-900">Riwayat setoran</h2>
                <span class="rounded-full bg-zinc-900 px-2.5 py-1 text-xs font-medium text-white">{{ $grup->total() }}</span>
            </div>

            <form method="GET" action="{{ route('nasabah.beranda') }}"
                  class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="flex-1 min-w-[130px]">
                    <label for="dari" class="mb-1.5 block text-xs font-medium text-zinc-500">Dari</label>
                    <input id="dari" name="dari" type="date" value="{{ $dari }}"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                </div>
                <div class="flex-1 min-w-[130px]">
                    <label for="sampai" class="mb-1.5 block text-xs font-medium text-zinc-500">Sampai</label>
                    <input id="sampai" name="sampai" type="date" value="{{ $sampai }}"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex h-[42px] items-center justify-center rounded-full bg-zinc-900 px-6 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 active:scale-[0.98] transition">Terapkan</button>
                    <a href="{{ route('nasabah.beranda') }}" class="inline-flex h-[42px] items-center justify-center rounded-full border border-zinc-200 bg-white px-5 text-sm font-medium text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 hover:bg-zinc-50 transition {{ ($dari || $sampai) ? '' : 'opacity-40 pointer-events-none' }}" aria-label="Reset filter">
                        <svg viewBox="0 0 24 24" class="mr-1.5 size-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        Reset
                    </a>
                </div>
            </form>

            @if ($grup->isEmpty())
                <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 py-12 text-center">
                    <p class="text-sm font-medium text-zinc-900">{{ $dari || $sampai ? 'Tidak ada data di periode ini' : 'Belum ada setoran' }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ $dari || $sampai ? 'Coba ubah rentang tanggal' : 'Mulai pilah sampah plastikmu' }}</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($grup as $g)
                        @php
                            $baris = $setoran->get($g->nomor_bukti) ?? collect();
                            $totalKg = $baris->sum('berat_gram') / 1000;
                            $totalRp = $baris->sum('total_rupiah');
                            $namaItem = $baris->map(fn ($x) => $x->kategori?->nama ?? '—')->implode(', ');
                            $pertama = $baris->first();
                        @endphp
                        @if ($pertama)
                        <a href="{{ route('nasabah.struk', $pertama->id) }}"
                           class="group flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-white px-5 py-4 shadow-sm transition hover:border-zinc-300 hover:shadow">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-900">{{ \Illuminate\Support\Carbon::parse($g->tanggal_terakhir)->format('d MMM Y') }}</p>
                                <p class="mt-0.5 truncate text-xs text-zinc-500">{{ $namaItem }}</p>
                                <p class="mt-1 inline-flex items-center gap-1.5 text-xs text-zinc-400"><span class="size-1 rounded-full bg-zinc-300"></span>{{ $baris->count() }} item · {{ $g->nomor_bukti }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-semibold text-zinc-900">Rp {{ number_format($totalRp, 0, ',', '.') }}</p>
                                <p class="text-xs text-zinc-500">{{ number_format($totalKg, 1, ',', '.') }} kg</p>
                            </div>
                            <span class="hidden size-7 place-items-center rounded-full border border-zinc-200 text-zinc-400 group-hover:border-zinc-900 group-hover:bg-zinc-900 group-hover:text-white sm:grid">→</span>
                        </a>
                        @endif
                    @endforeach
                </div>

                @if ($grup->hasPages())
                    <div class="mt-6">
                        {{ $grup->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
