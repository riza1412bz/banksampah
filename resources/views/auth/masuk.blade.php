@extends('layouts.app')

@section('judul', 'Masuk — Bank Sampah Indah Lestari')

@section('isi')
    <div class="mx-auto max-w-sm space-y-5">
        <div class="text-center">
            <h1 class="font-display text-2xl font-bold text-terpal">Masuk</h1>
            <p class="mt-1 text-sm text-karet/60">Lihat tabungan sampahmu.</p>
        </div>

        <form method="POST" action="{{ route('masuk') }}"
              class="space-y-4 rounded-3xl border-2 border-karet/15 bg-karung/70 p-5">
            @csrf

            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-karet">Email</label>
                <input id="email" name="email" type="email" required autocomplete="email" autofocus
                       value="{{ old('email') }}"
                       @error('email') aria-invalid="true" aria-describedby="galat-email" @enderror
                       class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none focus-visible:ring-2 focus-visible:ring-terpal/30">
                @error('email')
                    <p id="galat-email" class="mt-1 text-sm text-timbangan">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold text-karet">Kata sandi</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none focus-visible:ring-2 focus-visible:ring-terpal/30">
            </div>

            <label for="ingat" class="flex items-center gap-2 text-sm text-karet/70">
                <input id="ingat" name="ingat" type="checkbox" value="1"
                       class="size-4 rounded border-2 border-karet/30 text-terpal focus:ring-terpal/40">
                Ingat saya
            </label>

            <button type="submit"
                    class="w-full rounded-xl border-2 border-terpal bg-terpal px-4 py-3 font-semibold text-karung transition hover:bg-terpal-muda focus:outline-none focus-visible:ring-2 focus-visible:ring-karet focus-visible:ring-offset-2">
                Masuk
            </button>
        </form>

        <p class="text-center text-sm text-karet/60">
            Belum punya akun?
            <a href="{{ route('daftar') }}" class="font-semibold text-terpal underline decoration-2 underline-offset-2 hover:text-terpal-muda">Daftar jadi nasabah</a>
        </p>
    </div>
@endsection
