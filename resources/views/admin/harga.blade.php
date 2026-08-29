@extends('layouts.app')

@section('judul', 'Atur harga — Bank Sampah Indah Lestari')

@section('isi')
<div class="mx-auto max-w-[880px] space-y-8">

    @php $tanpaHarga = $kategori->filter(fn ($k) => !$k->harga_aktif_model); @endphp
    @if ($tanpaHarga->isNotEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <p class="text-sm font-medium text-amber-900">Kategori belum punya harga: {{ $tanpaHarga->pluck('nama')->join(', ') }}</p>
            <a href="{{ route('admin.harga.init-default') }}" class="mt-2 inline-flex rounded-full bg-zinc-900 px-4 py-1.5 text-xs font-medium text-white hover:bg-zinc-800" onclick="return confirm('Set harga default Rp 1.000/kg?')">Isi harga default</a>
        </div>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Harga per jenis</h1>
            <p class="mt-1 text-sm text-zinc-500">Harga aktif dipakai otomatis saat mencatat setoran.</p>
        </div>
        <a href="{{ route('admin.harga.kategori-baru') }}" class="inline-flex items-center gap-2 rounded-full bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-zinc-800">
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Tambah jenis
        </a>
    </div>

    @php $grouped = $kategori->groupBy(fn ($k) => $k->kelompok?->nama ?? 'Lainnya'); @endphp

    <div class="space-y-8">
        @foreach ($grouped as $namaKelompok => $items)
            <section class="space-y-3">
                <div class="flex items-baseline justify-between border-b border-zinc-200 pb-2">
                    <h2 class="text-sm font-semibold tracking-tight text-zinc-900">{{ $namaKelompok }}</h2>
                    <span class="text-xs tabular-nums text-zinc-400">{{ $items->count() }} jenis</span>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($items as $k)
                        <div class="group rounded-2xl border {{ $k->aktif ? 'border-zinc-200 bg-white' : 'border-zinc-200 bg-zinc-50 opacity-60' }} p-4 shadow-sm hover:border-zinc-300 hover:shadow transition">
                            <p class="text-sm font-medium text-zinc-900 {{ !$k->aktif ? 'line-through' : '' }}">
                                {{ $k->nama }}
                                @if ($k->kode)<span class="ml-1 rounded bg-zinc-100 px-1.5 py-0.5 text-xs font-normal text-zinc-500">{{ $k->kode }}</span>@endif
                            </p>
                            @if ($k->harga_aktif_model)
                                <p class="mt-1 text-sm font-semibold tabular-nums text-zinc-900">Rp {{ number_format($k->harga_aktif_model->harga_per_kg, 0, ',', '.') }}<span class="font-normal text-zinc-500 text-xs">/kg</span></p>
                            @else
                                <p class="mt-1 text-xs font-medium text-red-600">Belum ditetapkan</p>
                            @endif
                            <p class="mt-1 text-xs text-zinc-400">CO₂e {{ $k->faktor_emisi_kg_co2e !== null ? number_format($k->faktor_emisi_kg_co2e,3,',','.') : '—' }}</p>

                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @if ($k->aktif)
                                    <button type="button" data-kategori-id="{{ $k->id }}" data-nama="{{ $k->nama }}" data-harga="{{ $k->harga_aktif_model?->harga_per_kg ?? '' }}" class="buka-form-ubah rounded-full border border-zinc-900 bg-zinc-900 px-3 py-1 text-xs font-medium text-white hover:bg-zinc-800">Ubah harga</button>
                                @endif
                                <a href="{{ route('admin.harga.edit-kategori', $k->id) }}" class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 hover:border-zinc-900 hover:text-zinc-900">Edit</a>
                                <form method="POST" action="{{ route('admin.harga.destroy-kategori', $k->id) }}" class="inline" onsubmit="return confirm('Hapus {{ $k->nama }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-full border border-zinc-200 px-3 py-1 text-xs font-medium text-zinc-500 hover:border-red-200 hover:text-red-600">Hapus</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <div id="form-ubah-wrapper" class="hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.harga.ubah') }}" class="space-y-4">
            @csrf
            <input type="hidden" id="ubah_kategori_id" name="kategori_sampah_id">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-900">Ubah harga</h2>
                <span id="ubah_nama_label" class="rounded-full bg-zinc-900 px-3 py-1 text-xs font-medium text-white"></span>
            </div>
            <div>
                <label for="ubah_harga" class="mb-1.5 block text-sm font-medium text-zinc-700">Harga baru (Rp/kg)</label>
                <input id="ubah_harga" name="harga_per_kg" type="number" min="1" step="1" required class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
            </div>
            <div>
                <label for="ubah_berlaku_dari" class="mb-1.5 block text-sm font-medium text-zinc-700">Berlaku mulai <span class="font-normal text-zinc-400">(hari ini jika kosong)</span></label>
                <input id="ubah_berlaku_dari" name="berlaku_dari" type="date" class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="rounded-full bg-zinc-900 px-6 py-2.5 text-sm font-medium text-white hover:bg-zinc-800">Simpan</button>
                <button type="button" id="batal-ubah" class="rounded-full border border-zinc-200 bg-white px-6 py-2.5 text-sm font-medium text-zinc-600 hover:border-zinc-300">Batal</button>
            </div>
        </form>
    </div>

    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-zinc-900">Riwayat perubahan</h2>
        @if ($riwayat->isEmpty())
            <p class="mt-3 text-sm text-zinc-500">Belum ada perubahan.</p>
        @else
            <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50">
                        <tr class="text-xs uppercase tracking-widest text-zinc-500">
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Jenis</th>
                            <th class="px-4 py-3 text-right font-medium">Harga</th>
                            <th class="px-4 py-3 text-right font-medium">Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white">
                        @foreach ($riwayat as $r)
                            <tr class="hover:bg-zinc-50/70">
                                <td class="px-4 py-3 text-zinc-600 tabular-nums">{{ \Illuminate\Support\Carbon::parse($r->berlaku_dari)->format('d MMM Y') }}</td>
                                <td class="px-4 py-3 font-medium text-zinc-900">{{ $r->kategori->nama ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-zinc-900">Rp {{ number_format($r->harga_per_kg, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-zinc-500">{{ $r->dibuatOleh->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-right text-xs text-zinc-400">20 terbaru</p>
        @endif
    </section>
</div>

@endsection

@push('scripts')
    @vite('resources/js/pages/harga.js')
@endpush
