<!DOCTYPE html>
<html lang="id" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Bank Sampah Indah Lestari — catat setoran, hitung tabungan, jadwal setor. Kota Malang.">
    <meta name="theme-color" content="#18181b">
    <title>@yield('judul', 'Bank Sampah Indah Lestari')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-karung text-karet antialiased selection:bg-zinc-900 selection:text-white">

<a href="#utama" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-terpal focus:px-4 focus:py-2 focus:text-white">
    Lompat ke isi
</a>

<header class="sticky top-0 z-30 border-b border-zinc-200/70 bg-white/80 backdrop-blur-xl supports-[backdrop-filter]:bg-white/60">
    <div class="mx-auto max-w-[1120px] px-5 sm:px-6">
        <div class="flex h-[64px] items-center justify-between gap-4">
            <a href="{{ url('/') }}" class="group flex items-center gap-3.5">
                <span aria-hidden="true" class="grid size-8 shrink-0 place-items-center rounded-lg bg-zinc-900 text-white shadow-sm">
                    <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <path d="M12 3v4"/><path d="M5 7h14"/><path d="M5 7l-2 6h4z"/><path d="M19 7l-2 6h4z"/><path d="M9 20h6"/><path d="M12 7v13"/>
                    </svg>
                </span>
                <span class="leading-tight">
                    <span class="block text-[15px] font-semibold tracking-tight text-zinc-900">Bank Sampah Indah Lestari</span>
                    <span class="block text-xs font-medium tracking-wide text-zinc-500">Kota Malang · Est. 2024</span>
                </span>
            </a>

            @auth
                {{-- Desktop --}}
                <nav class="hidden items-center gap-1 text-[14px] lg:flex" aria-label="Menu utama">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-full px-3.5 py-2 font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">Dashboard</a>
                        <a href="{{ route('admin.setoran.create') }}" class="rounded-full px-3.5 py-2 font-medium transition {{ request()->routeIs('admin.setoran.create') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">Catat</a>
                        <a href="{{ route('admin.setoran.index') }}" class="rounded-full px-3.5 py-2 font-medium transition {{ request()->routeIs('admin.setoran.index') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">Setoran</a>
                        <a href="{{ route('admin.harga.index') }}" class="rounded-full px-3.5 py-2 font-medium transition {{ request()->routeIs('admin.harga.*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">Harga</a>
                        <a href="{{ route('admin.jadwal.index') }}" class="rounded-full px-3.5 py-2 font-medium transition {{ request()->routeIs('admin.jadwal.*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">Jadwal</a>
                        <a href="{{ route('admin.nasabah.index') }}" class="rounded-full px-3.5 py-2 font-medium transition {{ request()->routeIs('admin.nasabah.*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">Nasabah</a>
                    @else
                        <a href="{{ route('nasabah.beranda') }}" class="rounded-full px-3.5 py-2 font-medium transition {{ request()->routeIs('nasabah.beranda') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">Tabungan</a>
                        <a href="{{ route('nasabah.kalkulator') }}" class="rounded-full px-3.5 py-2 font-medium transition {{ request()->routeIs('nasabah.kalkulator') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">Hitung</a>
                        <a href="{{ route('nasabah.jadwal') }}" class="rounded-full px-3.5 py-2 font-medium transition {{ request()->routeIs('nasabah.jadwal') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">Jadwal</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="ml-2">
                        @csrf
                        <button type="submit" class="rounded-full border border-zinc-200 px-4 py-2 font-medium text-zinc-600 transition hover:border-zinc-900 hover:bg-zinc-900 hover:text-white">Keluar</button>
                    </form>
                </nav>

                <button type="button" id="tombol-menu"
                        class="inline-flex size-9 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:border-zinc-300 hover:text-zinc-900 lg:hidden"
                        aria-expanded="false" aria-controls="menu-mobile" aria-label="Buka menu">
                    <svg class="ikon-menu size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16"/><path d="M4 12h16"/></svg>
                    <svg class="ikon-tutup hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12"/><path d="M18 6L6 18"/></svg>
                </button>
            @endauth
        </div>

        @auth
            <div id="menu-mobile" class="menu-mobile lg:hidden" aria-hidden="true" inert>
                <nav class="flex flex-col gap-1.5 border-t border-zinc-100 py-4 text-sm" aria-label="Menu mobile">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-xl px-3 py-2.5 font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50' }}">Dashboard</a>
                        <a href="{{ route('admin.setoran.create') }}" class="rounded-xl px-3 py-2.5 font-medium {{ request()->routeIs('admin.setoran.create') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50' }}">Catat setor</a>
                        <a href="{{ route('admin.setoran.index') }}" class="rounded-xl px-3 py-2.5 font-medium {{ request()->routeIs('admin.setoran.index') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50' }}">Setoran</a>
                        <a href="{{ route('admin.harga.index') }}" class="rounded-xl px-3 py-2.5 font-medium {{ request()->routeIs('admin.harga.*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50' }}">Harga</a>
                        <a href="{{ route('admin.jadwal.index') }}" class="rounded-xl px-3 py-2.5 font-medium {{ request()->routeIs('admin.jadwal.*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50' }}">Jadwal</a>
                        <a href="{{ route('admin.nasabah.index') }}" class="rounded-xl px-3 py-2.5 font-medium {{ request()->routeIs('admin.nasabah.*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50' }}">Nasabah</a>
                    @else
                        <a href="{{ route('nasabah.beranda') }}" class="rounded-xl px-3 py-2.5 font-medium {{ request()->routeIs('nasabah.beranda') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50' }}">Tabungan</a>
                        <a href="{{ route('nasabah.kalkulator') }}" class="rounded-xl px-3 py-2.5 font-medium {{ request()->routeIs('nasabah.kalkulator') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50' }}">Hitung</a>
                        <a href="{{ route('nasabah.jadwal') }}" class="rounded-xl px-3 py-2.5 font-medium {{ request()->routeIs('nasabah.jadwal') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50' }}">Jadwal</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-zinc-100 pt-3">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-zinc-200 px-3 py-2.5 text-left font-medium text-zinc-600 hover:bg-red-50 hover:text-red-600">Keluar</button>
                    </form>
                </nav>
            </div>
        @endauth
    </div>
</header>

<main id="utama" class="mx-auto w-full max-w-[1120px] flex-1 px-5 py-8 sm:px-6 sm:py-10">
    @if (session('sukses'))
        <div role="status" class="mb-6 flex items-start gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3.5 shadow-sm">
            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-zinc-900 text-white">
                <svg aria-hidden="true" viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg>
            </span>
            <p class="text-sm font-medium leading-6 text-zinc-900">{{ session('sukses') }}</p>
        </div>
    @endif

    @if (session('gagal') || $errors->any())
        <div role="alert" class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3.5">
            <p class="text-sm font-semibold text-red-700">{{ session('gagal') ?? 'Periksa kembali isian:' }}</p>
            @if ($errors->any())
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @yield('isi')
</main>

<footer class="mt-12 border-t border-zinc-200 bg-white py-8">
    <div class="mx-auto max-w-[1120px] px-5 sm:px-6 flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-xs font-medium tracking-wide text-zinc-400">
            Bank Sampah Indah Lestari · Kota Malang
        </p>
        <p class="text-xs text-zinc-400">Harga mengikuti pengepul · Data tersimpan aman</p>
    </div>
</footer>

@stack('scripts')
</body>
</html>
