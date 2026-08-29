@extends('layouts.app')

@section('judul', 'Edit jadwal — Bank Sampah Indah Lestari')

@section('isi')
    <div class="mx-auto max-w-md space-y-5">
        <div>
            <h1 class="font-display text-2xl font-bold text-terpal">Edit jadwal</h1>
            <p class="mt-1 text-sm text-karet/60">
                {{ $j->tanggal->translatedFormat('l, j F Y') }}@if ($j->rentangJam()) · {{ $j->rentangJam() }}@endif
            </p>
        </div>

        <form method="POST" action="{{ route('admin.jadwal.update', $j) }}"
              class="space-y-4 rounded-3xl border-2 border-terpal/25 bg-pet/40 p-5">
            @csrf
            @method('PUT')

            <div>
                <label for="user_id" class="mb-1.5 block text-sm font-semibold text-karet">Untuk siapa</label>
                <select id="user_id" name="user_id"
                        class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 font-medium text-karet focus:border-terpal focus:outline-none focus-visible:ring-2 focus-visible:ring-terpal/30">
                    <option value="">Semua nasabah</option>
                    @foreach ($nasabah as $n)
                        <option value="{{ $n->id }}" @selected(old('user_id', $j->user_id) == $n->id)>
                            {{ $n->name }}@if ($n->kode_nasabah) — {{ $n->kode_nasabah }}@endif
                        </option>
                    @endforeach
                </select>
                @error('user_id')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="tanggal" class="mb-1.5 block text-sm font-semibold text-karet">Tanggal</label>
                <input id="tanggal" name="tanggal" type="date" required value="{{ old('tanggal', $j->tanggal->toDateString()) }}"
                       class="angka w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
                @error('tanggal')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="jam_mulai" class="mb-1.5 block text-sm font-semibold text-karet">Jam mulai</label>
                    <input id="jam_mulai" name="jam_mulai" type="time" value="{{ old('jam_mulai', $j->jam_mulai) }}"
                           class="angka w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
                    @error('jam_mulai')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="jam_selesai" class="mb-1.5 block text-sm font-semibold text-karet">Jam selesai</label>
                    <input id="jam_selesai" name="jam_selesai" type="time" value="{{ old('jam_selesai', $j->jam_selesai) }}"
                           class="angka w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
                    @error('jam_selesai')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="lokasi" class="mb-1.5 block text-sm font-semibold text-karet">
                    Lokasi <span class="font-normal text-karet/45">(boleh dikosongkan)</span>
                </label>
                <input id="lokasi" name="lokasi" type="text" value="{{ old('lokasi', $j->lokasi) }}" placeholder="mis. Balai RW 03"
                       class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
                @error('lokasi')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="keterangan" class="mb-1.5 block text-sm font-semibold text-karet">
                    Keterangan <span class="font-normal text-karet/45">(boleh dikosongkan)</span>
                </label>
                <textarea id="keterangan" name="keterangan" rows="2" placeholder="mis. bawa sampah yang sudah dipilah"
                          class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-sm text-karet focus:border-terpal focus:outline-none">{{ old('keterangan', $j->keterangan) }}</textarea>
                @error('keterangan')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
            </div>

            <button type="submit"
                    class="w-full rounded-xl border-2 border-terpal bg-terpal px-4 py-3 font-semibold text-karung transition hover:bg-terpal-muda focus:outline-none focus-visible:ring-2 focus-visible:ring-karet focus-visible:ring-offset-2">
                Simpan perubahan
            </button>
        </form>

        <p class="text-center">
            <a href="{{ route('admin.jadwal.index') }}" class="text-sm font-semibold text-karet/60 underline decoration-2 underline-offset-2 hover:text-terpal">
                Kembali ke jadwal setor
            </a>
        </p>
    </div>

    <script id="alamat-data" type="application/json">{!! json_encode($alamatNasabah) !!}</script>
    <script>window.__ALAMAT_NASABAH__ = JSON.parse(document.getElementById('alamat-data').textContent || '{}');</script>
@endsection

@push('scripts')
    @vite('resources/js/pages/jadwal.js')
@endpush
