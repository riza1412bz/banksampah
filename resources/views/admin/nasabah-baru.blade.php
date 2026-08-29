@extends('layouts.app')

@section('judul', 'Tambah nasabah — Bank Sampah Indah Lestari')

@section('isi')
    <div class="mx-auto max-w-[640px] space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Tambah nasabah</h1>
            <p class="mt-1.5 text-sm text-zinc-500">Kode dibuat otomatis: <span class="font-mono font-medium text-zinc-900">BSIL</span> untuk Perorangan, <span class="font-mono font-medium text-zinc-900">CORP</span> untuk Corporate.</p>
        </div>

        <form method="POST" action="{{ route('admin.nasabah.store') }}"
              class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="mb-1.5 block text-sm font-medium text-zinc-700">Nama lengkap</label>
                    <input id="name" name="name" type="text" required autofocus value="{{ old('name') }}"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    @error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label>
                    <input id="email" name="email" type="email" required value="{{ old('email') }}"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="jenis_nasabah" class="mb-1.5 block text-sm font-medium text-zinc-700">Jenis nasabah</label>
                    <select id="jenis_nasabah" name="jenis_nasabah" required
                            class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm font-medium text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        <option value="perorangan" @selected(old('jenis_nasabah','perorangan')==='perorangan')>Perorangan — BSIL</option>
                        <option value="corporate" @selected(old('jenis_nasabah')==='corporate')>Corporate — CORP</option>
                    </select>
                    @error('jenis_nasabah')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="telepon" class="mb-1.5 block text-sm font-medium text-zinc-700">Nomor HP <span class="font-normal text-zinc-400">(opsional)</span></label>
                    <input id="telepon" name="telepon" type="tel" value="{{ old('telepon') }}"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    @error('telepon')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-zinc-700">Kata sandi awal</label>
                    <input id="password" name="password" type="text" required autocomplete="off" aria-describedby="bantuan-sandi"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    <p id="bantuan-sandi" class="mt-1 text-xs text-zinc-500">Minimal 8 karakter — terlihat agar bisa dicatat.</p>
                    @error('password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            @include('admin._alamat-terstruktur', ['n' => null])

            <button type="submit" class="w-full rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 active:scale-[0.99] transition">
                Simpan nasabah
            </button>
        </form>

        <p class="text-center">
            <a href="{{ route('admin.nasabah.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900 underline decoration-zinc-300 underline-offset-4">Kembali ke daftar nasabah</a>
        </p>
    </div>
@endsection
