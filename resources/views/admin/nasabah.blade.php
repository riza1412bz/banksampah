@extends('layouts.app')

@section('judul', 'Nasabah — Bank Sampah Indah Lestari')

@section('isi')
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Nasabah</h1>
                <p class="mt-1 text-sm text-zinc-500">{{ $nasabah->total() }} terdaftar · <span class="font-medium text-zinc-900">BSIL</span> Perorangan · <span class="font-medium text-zinc-900">CORP</span> Corporate</p>
            </div>
            <a href="{{ route('admin.nasabah.create') }}" class="inline-flex items-center gap-2 rounded-full bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-zinc-800">
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Tambah nasabah
            </a>
        </div>

        <form method="GET" action="{{ route('admin.nasabah.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm" role="search">
            <div class="min-w-0 flex-1">
                <label for="cari" class="mb-1.5 block text-xs font-medium text-zinc-500">Cari</label>
                <input id="cari" name="cari" type="search" value="{{ $cari }}" placeholder="Nama, kode, atau email"
                       class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
            </div>
            <div>
                <label for="jenis" class="mb-1.5 block text-xs font-medium text-zinc-500">Jenis</label>
                <select id="jenis" name="jenis" class="rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none">
                    <option value="">Semua</option>
                    <option value="perorangan" @selected(($filterJenis ?? '')==='perorangan')>Perorangan (BSIL)</option>
                    <option value="corporate" @selected(($filterJenis ?? '')==='corporate')>Corporate (CORP)</option>
                </select>
            </div>
            <button type="submit" class="h-[42px] rounded-full bg-zinc-900 px-6 text-sm font-medium text-white hover:bg-zinc-800">Cari</button>
            @if ($cari !== '' || ($filterJenis ?? '') !== '')
                <a href="{{ route('admin.nasabah.index') }}" class="inline-flex h-[42px] items-center rounded-full border border-zinc-200 bg-white px-5 text-sm font-medium text-zinc-600 hover:bg-zinc-50">Reset</a>
            @endif
        </form>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            @if ($nasabah->isEmpty())
                <div class="rounded-xl border border-dashed border-zinc-200 bg-zinc-50 py-10 text-center">
                    <p class="text-sm text-zinc-500">
                        @if ($cari !== '' || ($filterJenis ?? '') !== '')
                            Tidak ada nasabah yang cocok.
                        @else
                            Belum ada nasabah.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto -mx-5">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Daftar nasabah</caption>
                        <thead class="bg-zinc-50">
                            <tr class="text-xs uppercase tracking-widest text-zinc-500">
                                <th scope="col" class="px-5 py-3 font-medium">Nama</th>
                                <th scope="col" class="px-3 py-3 font-medium">Kode</th>
                                <th scope="col" class="px-3 py-3 font-medium">Jenis</th>
                                <th scope="col" class="px-3 py-3 text-right font-medium">Setor</th>
                                <th scope="col" class="px-3 py-3 text-right font-medium">Berat</th>
                                <th scope="col" class="px-3 py-3 text-right font-medium">Tabungan</th>
                                <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 bg-white">
                            @foreach ($nasabah as $n)
                                <tr class="hover:bg-zinc-50/70">
                                    <td class="px-5 py-3">
                                        <p class="text-sm font-medium text-zinc-900">{{ $n->name }}</p>
                                        <p class="truncate text-xs text-zinc-500">{{ $n->email }}</p>
                                        @unless ($n->aktif)
                                            <span class="mt-1 inline-block rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600">nonaktif</span>
                                        @endunless
                                    </td>
                                    <td class="px-3 py-3 font-mono text-xs font-medium {{ str_starts_with($n->kode_nasabah ?? '', 'CORP') ? 'text-violet-600' : 'text-zinc-900' }}">{{ $n->kode_nasabah ?? '—' }}</td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ ($n->jenis_nasabah ?? 'perorangan') === 'corporate' ? 'bg-violet-50 text-violet-700 border border-violet-200' : 'bg-zinc-100 text-zinc-600 border border-zinc-200' }}">
                                            {{ ($n->jenis_nasabah ?? 'perorangan') === 'corporate' ? 'Corporate' : 'Perorangan' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums text-zinc-600">{{ $n->setoran_count }}x</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-zinc-600">
                                        {{ number_format(($n->jumlah_berat_gram ?? 0) / 1000, 1, ',', '.') }} kg
                                    </td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums text-zinc-900">
                                        Rp {{ number_format($n->jumlah_rupiah ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.nasabah.edit', $n) }}" class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 hover:border-zinc-900 hover:text-zinc-900">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.nasabah.toggle-aktif', $n) }}">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit"
                                                        title="{{ $n->aktif ? 'Nonaktifkan' : 'Aktifkan' }} {{ $n->name }}"
                                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition {{ $n->aktif ? 'bg-zinc-900' : 'bg-zinc-200' }} focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900">
                                                    <span aria-hidden="true" class="inline-block size-4 rounded-full bg-white shadow-sm transition {{ $n->aktif ? 'translate-x-[1.35rem]' : 'translate-x-1' }}"></span>
                                                    <span class="sr-only">{{ $n->aktif ? 'Nonaktifkan' : 'Aktifkan' }} {{ $n->name }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($nasabah->hasPages())
                    <div class="mt-4">{{ $nasabah->links() }}</div>
                @endif
            @endif
        </section>
    </div>
@endsection
