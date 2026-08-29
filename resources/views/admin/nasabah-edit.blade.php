@extends('layouts.app')

@section('judul', 'Edit nasabah — Bank Sampah Indah Lestari')

@section('isi')
    <div class="mx-auto max-w-[640px] space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Edit nasabah</h1>
            <p class="mt-1 flex items-center gap-2 text-sm text-zinc-500">
                {{ $n->name }}
                <span class="font-mono text-xs font-medium {{ str_starts_with($n->kode_nasabah ?? '', 'CORP') ? 'text-violet-600' : 'text-zinc-900' }}">{{ $n->kode_nasabah }}</span>
                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ ($n->jenis_nasabah ?? 'perorangan') === 'corporate' ? 'bg-violet-50 text-violet-700 border border-violet-200' : 'bg-zinc-100 text-zinc-600 border border-zinc-200' }}">{{ ($n->jenis_nasabah ?? 'perorangan') === 'corporate' ? 'Corporate' : 'Perorangan' }}</span>
            </p>
        </div>

        <form method="POST" action="{{ route('admin.nasabah.update', $n) }}"
              class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium text-zinc-700">Nama lengkap</label>
                    <input id="name" name="name" type="text" required value="{{ old('name', $n->name) }}"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    @error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="jenis_nasabah" class="mb-1.5 block text-sm font-medium text-zinc-700">Jenis nasabah</label>
                    <select id="jenis_nasabah" name="jenis_nasabah" required
                            class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        <option value="perorangan" @selected(old('jenis_nasabah', $n->jenis_nasabah ?? 'perorangan') === 'perorangan')>Perorangan — BSIL</option>
                        <option value="corporate" @selected(old('jenis_nasabah', $n->jenis_nasabah) === 'corporate')>Corporate — CORP</option>
                    </select>
                    <p class="mt-1 text-xs text-zinc-400">Kode tetap {{ $n->kode_nasabah }} (tidak berubah).</p>
                    @error('jenis_nasabah')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="email" class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label>
                    <input id="email" name="email" type="email" required value="{{ old('email', $n->email) }}"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="telepon" class="mb-1.5 block text-sm font-medium text-zinc-700">Nomor HP</label>
                    <input id="telepon" name="telepon" type="tel" value="{{ old('telepon', $n->telepon) }}"
                           class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                    @error('telepon')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kode nasabah</label>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2.5 text-sm font-mono text-zinc-700">{{ $n->kode_nasabah }}</div>
                </div>
            </div>

            @include('admin._alamat-terstruktur')

            <button type="submit" class="w-full rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 active:scale-[0.99] transition">
                Simpan perubahan
            </button>
        </form>

        {{-- Reset password --}}
        <form method="POST" action="{{ route('admin.nasabah.reset-sandi', $n) }}"
              class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <h2 class="text-sm font-semibold text-zinc-900">Reset kata sandi</h2>
            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-zinc-700">Sandi baru</label>
                <input id="password" name="password" type="text" required autocomplete="off"
                       class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900"
                       aria-describedby="bantuan-reset">
                <p id="bantuan-reset" class="mt-1.5 text-xs text-zinc-500">Minimal 8 karakter — terlihat agar bisa disampaikan ke nasabah.</p>
                @error('password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="w-full rounded-full bg-white px-6 py-2.5 text-sm font-medium text-zinc-900 border border-zinc-200 hover:bg-zinc-50">Reset sandi</button>
        </form>

        {{-- Status aktif --}}
        <form method="POST" action="{{ route('admin.nasabah.toggle-aktif', $n) }}"
              class="flex items-center justify-between gap-4 rounded-2xl border {{ $n->aktif ? 'border-zinc-200 bg-white' : 'border-red-200 bg-red-50' }} p-5 shadow-sm">
            @csrf
            @method('PUT')
            <div>
                <h2 class="text-sm font-semibold {{ $n->aktif ? 'text-zinc-900' : 'text-red-700' }}">{{ $n->aktif ? 'Aktif' : 'Nonaktif' }}</h2>
                <p class="mt-1 text-xs text-zinc-500">{{ $n->aktif ? 'Nasabah bisa login dan setor.' : 'Tidak bisa login, riwayat tetap utuh.' }}</p>
            </div>
            <button type="submit" class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition {{ $n->aktif ? 'bg-zinc-900' : 'bg-zinc-300' }}">
                <span class="inline-block size-4 rounded-full bg-white shadow-sm transition {{ $n->aktif ? 'translate-x-6' : 'translate-x-1' }}"></span>
            </button>
        </form>

        @if (! $sudahSetor)
            <form method="POST" action="{{ route('admin.nasabah.destroy', $n) }}"
                  class="space-y-3 rounded-2xl border border-red-200 bg-red-50 p-6">
                @csrf
                @method('DELETE')
                <h2 class="text-sm font-semibold text-red-700">Hapus nasabah</h2>
                <p class="text-xs leading-6 text-zinc-600">{{ $n->name }} belum pernah setor, bisa dihapus permanen. Tidak bisa dibatalkan.</p>
                <button type="submit" onclick="return confirm('Hapus permanen {{ $n->name }}?')" class="w-full rounded-full bg-red-600 px-6 py-3 text-sm font-medium text-white hover:bg-red-700">Hapus nasabah ini</button>
            </form>
        @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="text-sm font-semibold text-amber-900">Hapus nasabah</h2>
                <p class="mt-1 text-xs leading-6 text-zinc-600">{{ $n->name }} sudah pernah setor sampah, tidak bisa dihapus supaya riwayat tetap utuh. Nonaktifkan saja.</p>
            </div>
        @endif

        <p class="text-center">
            <a href="{{ route('admin.nasabah.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900 underline decoration-zinc-300 underline-offset-4">Kembali ke daftar nasabah</a>
        </p>
    </div>
@endsection
