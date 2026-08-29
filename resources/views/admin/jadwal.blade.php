@extends('layouts.app')

@section('judul', 'Jadwal setor — Bank Sampah Indah Lestari')

@section('isi')
    <div class="space-y-5">
        <div>
            <h1 class="font-display text-2xl font-bold text-terpal">Jadwal setor</h1>
            <p class="mt-1 text-sm text-karet/60">
                Kosongkan pilihan nasabah kalau jadwalnya berlaku untuk semua warga.
            </p>
        </div>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,340px)] lg:items-start">

            <section aria-labelledby="judul-mendatang" class="rounded-3xl border-2 border-karet/15 bg-karung/70 p-5">
                <h2 id="judul-mendatang" class="font-display text-lg font-bold text-karet">Mendatang</h2>
                <p class="mb-3 text-xs text-karet/50">{{ $mendatang->count() }} jadwal</p>

                @if ($mendatang->isEmpty())
                    <p class="py-8 text-center text-sm text-karet/50">Belum ada jadwal mendatang.</p>
                @else
                    <div class="overflow-x-auto rounded-2xl border-2 border-karet/12 bg-karung/50">
                        <table class="w-full min-w-[560px] border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b-2 border-karet/15 text-[0.7rem] uppercase tracking-wide text-karet/50">
                                    <th scope="col" class="px-4 py-2.5 font-semibold">Tanggal</th>
                                    <th scope="col" class="px-4 py-2.5 font-semibold">Jam</th>
                                    <th scope="col" class="px-4 py-2.5 font-semibold">Untuk</th>
                                    <th scope="col" class="px-4 py-2.5 font-semibold">Lokasi</th>
                                    <th scope="col" class="px-4 py-2.5 font-semibold">Keterangan</th>
                                    <th scope="col" class="px-4 py-2.5"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-karet/8">
                                @foreach ($mendatang as $j)
                                    @php
                                        // Lokasi otomatis dari alamat nasabah untuk jadwal khusus;
                                        // jadwal umum memakai lokasi manual yang diketik admin.
                                        $lokasiTampil = $j->untukSemua()
                                            ? $j->lokasi
                                            : ($j->user?->alamatLengkap() ?? $j->lokasi);
                                    @endphp
                                    <tr class="align-top">
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-karet">{{ $j->tanggal->translatedFormat('l, j F Y') }}</p>
                                        </td>
                                        <td class="angka whitespace-nowrap px-4 py-3 text-karet/65">
                                            {{ $j->rentangJam() ?? 'jam belum ditentukan' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($j->untukSemua())
                                                <span class="inline-block rounded-lg bg-terpal/12 px-2 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-terpal">
                                                    semua nasabah
                                                </span>
                                            @else
                                                {{-- Kontras dinaikkan: teks karet penuh di atas nota/40 lolos 4.5:1. --}}
                                                <span class="inline-block rounded-lg bg-nota/40 px-2 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-karet">
                                                    {{ $j->user->name ?? 'nasabah dihapus' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-karet/70">
                                            @if ($lokasiTampil)
                                                <span class="flex items-start gap-1.5">
                                                    <svg aria-hidden="true" viewBox="0 0 24 24" class="mt-0.5 size-4 shrink-0 text-karet/40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                        <path d="M12 21s-7-6-7-11a7 7 0 1114 0c0 5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>
                                                    </svg>
                                                    <span>{{ $lokasiTampil }}</span>
                                                </span>
                                            @else
                                                <span class="text-karet/40">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-karet/55">
                                            {{ $j->keterangan ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-1.5">
                                                <a href="{{ route('admin.jadwal.edit', $j) }}"
                                                   class="rounded-lg border-2 border-karet/15 px-2.5 py-1 text-xs font-semibold text-karet/60 transition hover:border-terpal hover:text-terpal focus:outline-none focus-visible:ring-2 focus-visible:ring-karet">
                                                    Edit
                                                </a>
                                                <form method="POST" action="{{ route('admin.jadwal.destroy', $j) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            onclick="return confirm('Hapus jadwal {{ $j->tanggal->translatedFormat('j M Y') }}?')"
                                                            class="rounded-lg border-2 border-karet/15 px-2.5 py-1 text-xs font-semibold text-karet/60 transition hover:border-timbangan hover:text-timbangan focus:outline-none focus-visible:ring-2 focus-visible:ring-karet">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <form method="POST" action="{{ route('admin.jadwal.store') }}"
                  class="space-y-4 rounded-3xl border-2 border-terpal/25 bg-pet/40 p-5">
                @csrf
                <h2 class="font-display text-lg font-bold text-terpal">Tambah jadwal</h2>

                <div>
                    <label for="user_id" class="mb-1.5 block text-sm font-semibold text-karet">Untuk siapa</label>
                    <select id="user_id" name="user_id"
                            class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 font-medium text-karet focus:border-terpal focus:outline-none focus-visible:ring-2 focus-visible:ring-terpal/30">
                        <option value="">Semua nasabah</option>
                        @foreach ($nasabah as $n)
                            <option value="{{ $n->id }}" @selected(old('user_id') == $n->id)>
                                {{ $n->name }}@if ($n->kode_nasabah) — {{ $n->kode_nasabah }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="tanggal" class="mb-1.5 block text-sm font-semibold text-karet">Tanggal</label>
                    <input id="tanggal" name="tanggal" type="date" required value="{{ old('tanggal') }}"
                           class="angka w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
                    @error('tanggal')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="jam_mulai" class="mb-1.5 block text-sm font-semibold text-karet">Jam mulai</label>
                        <input id="jam_mulai" name="jam_mulai" type="time" value="{{ old('jam_mulai') }}"
                               class="angka w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
                        @error('jam_mulai')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="jam_selesai" class="mb-1.5 block text-sm font-semibold text-karet">Jam selesai</label>
                        <input id="jam_selesai" name="jam_selesai" type="time" value="{{ old('jam_selesai') }}"
                               class="angka w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
                        @error('jam_selesai')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="lokasi" class="mb-1.5 block text-sm font-semibold text-karet">
                        Lokasi <span class="font-normal text-karet/45">(boleh dikosongkan)</span>
                    </label>
                    <input id="lokasi" name="lokasi" type="text" value="{{ old('lokasi') }}" placeholder="mis. Balai RW 03"
                           class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
                    @error('lokasi')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="keterangan" class="mb-1.5 block text-sm font-semibold text-karet">
                        Keterangan <span class="font-normal text-karet/45">(boleh dikosongkan)</span>
                    </label>
                    <textarea id="keterangan" name="keterangan" rows="2" placeholder="mis. bawa sampah yang sudah dipilah"
                              class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-sm text-karet focus:border-terpal focus:outline-none">{{ old('keterangan') }}</textarea>
                    @error('keterangan')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
                </div>

                <button type="submit"
                        class="w-full rounded-xl border-2 border-terpal bg-terpal px-4 py-3 font-semibold text-karung transition hover:bg-terpal-muda focus:outline-none focus-visible:ring-2 focus-visible:ring-karet focus-visible:ring-offset-2">
                    Simpan jadwal
                </button>
            </form>
        </div>

        @if ($lalu->isNotEmpty())
            <section aria-labelledby="judul-lalu" class="rounded-3xl border-2 border-karet/15 bg-karung/70 p-5">
                <h2 id="judul-lalu" class="font-display text-lg font-bold text-karet">Sudah lewat</h2>
                <p class="mb-3 text-xs text-karet/50">{{ $lalu->count() }} jadwal terakhir</p>

                <ul class="divide-y-2 divide-karet/8">
                    @foreach ($lalu as $j)
                        <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2.5 text-sm">
                            <p class="min-w-0 flex-1 text-karet/60">
                                {{ $j->tanggal->translatedFormat('D, j M Y') }}
                                <span class="angka text-karet/45">{{ $j->rentangJam() ? '· '.$j->rentangJam() : '' }}</span>
                            </p>
                            <p class="shrink-0 text-xs text-karet/50">
                                {{ $j->untukSemua() ? 'semua nasabah' : ($j->user->name ?? '—') }}
                            </p>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <a href="{{ route('admin.jadwal.edit', $j) }}"
                                   class="rounded-lg border-2 border-karet/15 px-2 py-0.5 text-xs font-semibold text-karet/55 transition hover:border-terpal hover:text-terpal">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.jadwal.destroy', $j) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            onclick="return confirm('Hapus jadwal {{ $j->tanggal->translatedFormat('j M Y') }}?')"
                                            class="rounded-lg border-2 border-karet/15 px-2 py-0.5 text-xs font-semibold text-karet/55 transition hover:border-timbangan hover:text-timbangan">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    {{-- Lokasi otomatis terisi alamat nasabah saat memilih "Untuk siapa" --}}
    <script id="alamat-data" type="application/json">{!! json_encode($alamatNasabah) !!}</script>
    <script>window.__ALAMAT_NASABAH__ = JSON.parse(document.getElementById('alamat-data').textContent || '{}');</script>
@endsection

@push('scripts')
    @vite('resources/js/pages/jadwal.js')
@endpush
