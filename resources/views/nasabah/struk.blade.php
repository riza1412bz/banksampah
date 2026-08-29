@extends('layouts.app')

@section('judul', 'Struk setoran — Bank Sampah Indah Lestari')

@section('isi')
    @php
        $items = $setoran->grup;
        $totalKg = $items->sum('berat_gram') / 1000;
        $totalRp = $items->sum('total_rupiah');
    @endphp

    <div class="mx-auto max-w-[480px] space-y-6">
        <div class="text-center print:hidden">
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Struk setoran</h1>
            <p class="mt-1 text-sm text-zinc-500">
                {{ $setoran->tanggal_setor->format('d M Y') }}
                — <span class="font-mono font-medium text-zinc-900">{{ $setoran->nomor_bukti }}</span>
            </p>
        </div>

        {{-- Kartu struk — clean & print-safe (bg putih solid, border hitam) --}}
        <div class="tepi-struk overflow-hidden rounded-2xl border border-zinc-900 bg-white shadow-sm print:shadow-none">
            {{-- Header struk --}}
            <div class="border-b border-zinc-900 px-6 py-5 text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-900">Bank Sampah Indah Lestari</p>
                <p class="mt-1 text-xs text-zinc-500">Malang — Tabungan plastik warga · {{ $setoran->tanggal_setor->format('d M Y') }}</p>
                <p class="mt-2 inline-flex rounded-full bg-zinc-900 px-3 py-1 font-mono text-xs font-medium text-white">{{ $setoran->nomor_bukti }}</p>
            </div>

            <div class="space-y-2 border-b border-zinc-200 px-6 py-4 text-sm">
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-500">Nasabah</span>
                    <span class="text-right font-medium text-zinc-900">{{ $setoran->user->name }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-500">Kode</span>
                    <span class="font-mono text-right text-sm font-medium text-zinc-900">{{ $setoran->user->kode_nasabah ?? '—' }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-500">Jenis</span>
                    <span class="text-right">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ ($setoran->user->jenis_nasabah ?? 'perorangan') === 'corporate' ? 'bg-violet-50 text-violet-700 border border-violet-200' : 'bg-zinc-100 text-zinc-600 border border-zinc-200' }}">{{ ($setoran->user->jenis_nasabah ?? 'perorangan') === 'corporate' ? 'Corporate' : 'Perorangan' }}</span>
                    </span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-500">Petugas</span>
                    <span class="text-right text-zinc-900">{{ $setoran->dicatatOleh->name ?? '—' }}</span>
                </div>
                @if ($setoran->catatan)
                    <div class="flex justify-between gap-4">
                        <span class="text-zinc-500">Catatan</span>
                        <span class="max-w-[60%] text-right italic text-zinc-700">{{ $setoran->catatan }}</span>
                    </div>
                @endif
            </div>

            {{-- Rincian item --}}
            <div class="border-b border-zinc-200 px-6 py-4">
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-400">Rincian</p>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 text-xs uppercase tracking-wide text-zinc-400">
                            <th class="pb-2 text-left font-medium">Jenis</th>
                            <th class="pb-2 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($items as $i)
                            <tr>
                                <td class="py-3 pr-4">
                                    <span class="font-medium text-zinc-900">{{ $i->kategori?->nama ?? '—' }}</span>
                                    <span class="mt-0.5 block text-xs tabular-nums text-zinc-500">
                                        {{ number_format($i->berat_gram / 1000, 2, ',', '.') }} kg × Rp {{ number_format($i->harga_per_kg, 0, ',', '.') }}/kg
                                    </span>
                                </td>
                                <td class="py-3 pl-2 text-right font-semibold tabular-nums text-zinc-900">
                                    Rp {{ number_format($i->total_rupiah, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Total --}}
            <div class="flex items-center justify-between bg-zinc-50 px-6 py-5 print:bg-white">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400">Total</p>
                    <p class="mt-1 text-xs tabular-nums text-zinc-500">{{ number_format($totalKg, 2, ',', '.') }} kg</p>
                </div>
                <p class="text-2xl font-semibold tracking-tight text-zinc-900">Rp {{ number_format($totalRp, 0, ',', '.') }}</p>
            </div>

            <div class="border-t border-zinc-200 bg-white px-6 py-3 text-center">
                <p class="text-xs text-zinc-400">Simpan struk ini sebagai bukti. Harga dibekukan saat setor — tidak berubah walau harga master naik.</p>
            </div>
        </div>

        <div class="flex justify-center gap-3 print:hidden">
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-full bg-zinc-900 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 active:scale-[0.98] transition">
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/></svg>
                Cetak / Simpan PDF
            </button>
            <a href="{{ route('nasabah.beranda') }}"
               class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-6 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                Kembali
            </a>
        </div>
    </div>
@endsection
