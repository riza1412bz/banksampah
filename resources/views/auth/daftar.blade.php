@extends('layouts.app')

@section('judul', 'Daftar nasabah — Bank Sampah Indah Lestari')

@section('isi')
    <div class="mx-auto max-w-[480px] space-y-6">
        <div class="text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Daftar jadi nasabah</h1>
            <p class="mt-2 text-sm leading-6 text-zinc-500">Warga atau corporate, gratis. Kode nasabah dibuat otomatis.</p>
        </div>

        <form method="POST" action="{{ route('daftar') }}"
              class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium text-zinc-700">Nama lengkap <span class="font-normal text-zinc-400">(perorangan atau perusahaan)</span></label>
                <input id="name" name="name" type="text" required autocomplete="name" autofocus value="{{ old('name') }}"
                       class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                @error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label>
                <input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email') }}"
                       class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="jenis_nasabah" class="mb-1.5 block text-sm font-medium text-zinc-700">Jenis nasabah</label>
                <select id="jenis_nasabah" name="jenis_nasabah" required
                        class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm font-medium text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    <option value="perorangan" @selected(old('jenis_nasabah','perorangan') === 'perorangan')>Perorangan — kode BSIL</option>
                    <option value="corporate" @selected(old('jenis_nasabah') === 'corporate')>Corporate — kode CORP</option>
                </select>
                <p class="mt-1 text-xs text-zinc-500">Perorangan untuk warga, Corporate untuk instansi/perusahaan.</p>
                @error('jenis_nasabah')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="telepon" class="mb-1.5 block text-sm font-medium text-zinc-700">Nomor HP <span class="font-normal text-zinc-400">(opsional)</span></label>
                    <input id="telepon" name="telepon" type="tel" autocomplete="tel" value="{{ old('telepon') }}"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    @error('telepon')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="alamat" class="mb-1.5 block text-sm font-medium text-zinc-700">Alamat <span class="font-normal text-zinc-400">(opsional)</span></label>
                    <input id="alamat" name="alamat" type="text" autocomplete="street-address" value="{{ old('alamat') }}"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    @error('alamat')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-zinc-700">Kata sandi</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" aria-describedby="bantuan-sandi"
                       class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                <p id="bantuan-sandi" class="mt-1.5 text-xs text-zinc-500">Minimal 8 karakter.</p>
                @error('password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-zinc-700">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                       class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
            </div>

            <button type="submit" class="w-full rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 active:scale-[0.99] transition">
                Daftar
            </button>
        </form>

        <p class="text-center text-sm text-zinc-500">
            Sudah punya akun?
            <a href="{{ route('masuk') }}" class="font-medium text-zinc-900 underline decoration-zinc-300 underline-offset-4 hover:decoration-zinc-900">Masuk</a>
        </p>
    </div>
@endsection
