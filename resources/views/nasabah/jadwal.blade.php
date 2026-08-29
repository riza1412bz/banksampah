@extends('layouts.app')

@section('judul', 'Jadwal setor — Bank Sampah Indah Lestari')

@section('isi')
    <div class="mx-auto max-w-lg space-y-5">
        <div>
            <h1 class="font-display text-2xl font-bold text-terpal">Jadwal setor</h1>
            <p class="mt-1 text-sm text-karet/60">Kapan kamu bisa datang bawa sampah.</p>
        </div>

        <section aria-labelledby="judul-mendatang" class="rounded-3xl border-2 border-karet/15 bg-karung/70 p-5">
            <h2 id="judul-mendatang" class="font-display text-lg font-bold text-karet">Mendatang</h2>
            <p class="mb-3 text-xs text-karet/50">{{ $mendatang->count() }} jadwal</p>

            @if ($mendatang->isEmpty())
                <div class="rounded-2xl border-2 border-dashed border-karet/20 px-4 py-8 text-center">
                    <p class="font-medium text-karet/70">Belum ada jadwal</p>
                    <p class="mt-1 text-sm text-karet/50">Pengurus belum menetapkan jadwal setor berikutnya.</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-2xl border-2 border-karet/12 bg-karung/50">
                    <table class="w-full min-w-[520px] border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b-2 border-karet/15 text-[0.7rem] uppercase tracking-wide text-karet/50">
                                <th scope="col" class="px-4 py-2.5 font-semibold">Tanggal</th>
                                <th scope="col" class="px-4 py-2.5 font-semibold">Jam</th>
                                <th scope="col" class="px-4 py-2.5 font-semibold">Lokasi</th>
                                <th scope="col" class="px-4 py-2.5 font-semibold">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-karet/8">
                            @foreach ($mendatang as $i => $j)
                                @php
                                    // Jadwal khusus untuk kamu: lokasi otomatis dari alamatmu sendiri.
                                    $lokasiTampil = $j->untukSemua()
                                        ? $j->lokasi
                                        : ($j->user?->alamatLengkap() ?? $j->lokasi);
                                @endphp
                                {{-- Jadwal terdekat ditonjolkan: itu yang paling dibutuhkan nasabah. --}}
                                <tr class="align-top {{ $i === 0 ? 'bg-nota/10' : '' }}">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-karet">{{ $j->tanggal->translatedFormat('l, j F Y') }}</p>
                                        @if ($i === 0)
                                            <span class="mt-1 inline-block rounded-lg bg-nota/25 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-karet/75">
                                                terdekat
                                            </span>
                                        @endif
                                    </td>
                                    <td class="angka whitespace-nowrap px-4 py-3 text-karet/65">
                                        {{ $j->rentangJam() ?? 'jam belum ditentukan' }}
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @unless ($mendatang->where(fn ($j) => ! $j->untukSemua())->isEmpty())
                    <p class="text-xs text-karet/50">Jadwal khusus untuk kamu pakai alamat yang tersimpan di profilmu.</p>
                @endunless
            @endif
        </section>

        @if ($lalu->isNotEmpty())
            <section aria-labelledby="judul-lalu" class="rounded-3xl border-2 border-karet/15 bg-karung/70 p-5">
                <h2 id="judul-lalu" class="font-display text-lg font-bold text-karet">Sudah lewat</h2>
                <p class="mb-3 text-xs text-karet/50">{{ $lalu->count() }} jadwal terakhir</p>

                <ul class="divide-y-2 divide-karet/8">
                    @foreach ($lalu as $j)
                        <li class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 py-2.5">
                            <p class="min-w-0 text-karet/60">{{ $j->tanggal->translatedFormat('D, j M Y') }}</p>
                            <p class="angka shrink-0 text-sm text-karet/45">{{ $j->rentangJam() ?? '—' }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <p class="text-center">
            <a href="{{ route('nasabah.beranda') }}" class="text-sm font-semibold text-karet/60 underline decoration-2 underline-offset-2 hover:text-terpal">
                Kembali ke tabunganku
            </a>
        </p>
    </div>
@endsection
