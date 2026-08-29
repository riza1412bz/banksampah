{{-- NIK + alamat terstruktur (dropdown Kota → Kecamatan → Desa/Kelurahan).
       Diterima: $n (User|null, untuk prefill edit), $daftarKota, $wilayah. --}}

<div>
    <label for="nik" class="mb-1.5 block text-sm font-semibold text-karet">NIK</label>
    <input id="nik" name="nik" type="text" inputmode="numeric" maxlength="16" required
           autocomplete="off" value="{{ old('nik', $n?->nik) }}"
           class="angka w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none focus-visible:ring-2 focus-visible:ring-terpal/30">
    @error('nik')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
</div>

<div class="space-y-4 rounded-2xl border-2 border-karet/10 bg-karung/40 p-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-karet/45">Alamat</p>

    <div>
        <label for="kota" class="mb-1.5 block text-sm font-semibold text-karet">Kota/Kabupaten</label>
        <select id="kota" name="kota" required
                class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
            <option value="" selected disabled>Pilih kota/kabupaten</option>
            @foreach ($daftarKota as $kota)
                <option value="{{ $kota }}" @selected(old('kota', $n?->kota) === $kota)>{{ $kota }}</option>
            @endforeach
        </select>
        @error('kota')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="kecamatan" class="mb-1.5 block text-sm font-semibold text-karet">Kecamatan</label>
        <select id="kecamatan" name="kecamatan" required disabled
                class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
            <option value="" selected disabled>Pilih kota dulu</option>
        </select>
        @error('kecamatan')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="desa_kelurahan" class="mb-1.5 block text-sm font-semibold text-karet">Desa/Kelurahan</label>
        <select id="desa_kelurahan" name="desa_kelurahan" required disabled
                class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
            <option value="" selected disabled>Pilih kecamatan dulu</option>
        </select>
        @error('desa_kelurahan')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="jalan" class="mb-1.5 block text-sm font-semibold text-karet">Jalan</label>
            <input id="jalan" name="jalan" type="text" required maxlength="255" autocomplete="address-line1"
                   value="{{ old('jalan', $n?->jalan) }}"
                   class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
            @error('jalan')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="rt_rw" class="mb-1.5 block text-sm font-semibold text-karet">RT/RW</label>
            <input id="rt_rw" name="rt_rw" type="text" required maxlength="10" placeholder="001/002"
                   value="{{ old('rt_rw', $n?->rt_rw) }}"
                   class="angka w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
            @error('rt_rw')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="detail_rumah" class="mb-1.5 block text-sm font-semibold text-karet">
            Detail rumah <span class="font-normal text-karet/45">(opsional)</span>
        </label>
        <input id="detail_rumah" name="detail_rumah" type="text" maxlength="200" autocomplete="address-line2"
               placeholder="Contoh: Blok B No. 12"
               value="{{ old('detail_rumah', $n?->detail_rumah) }}"
               class="w-full rounded-xl border-2 border-karet/20 bg-karung px-3 py-2.5 text-karet focus:border-terpal focus:outline-none">
        @error('detail_rumah')<p class="mt-1 text-sm text-timbangan">{{ $message }}</p>@enderror
    </div>
</div>

{{-- Data wilayah di-inject sebagai JSON terpisah agar bisa di-cache file JS eksternal --}}
<script id="wilayah-data" type="application/json">{!! json_encode($wilayah) !!}</script>
<script>
    window.__WILAYAH__ = JSON.parse(document.getElementById('wilayah-data').textContent || '{}');
    window.__KEC_PILIH__ = @json(old('kecamatan', $n?->kecamatan));
    window.__DESA_PILIH__ = @json(old('desa_kelurahan', $n?->desa_kelurahan));
</script>
@vite('resources/js/pages/alamat-terstruktur.js')
