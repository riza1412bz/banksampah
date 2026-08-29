@props([
    'beratGram' => 0,
    'rupiah' => 0,
    'skalaKg' => 50,
])

@php
    $beratKg = $beratGram / 1000;
    // Jarum bergerak -88deg (kosong) sampai +88deg (skala penuh).
    $rasio = $skalaKg > 0 ? min($beratKg / $skalaKg, 1) : 0;
    $sudut = round(-88 + ($rasio * 176), 2);
@endphp

<section aria-labelledby="judul-timbangan"
         class="relative overflow-hidden rounded-3xl border-2 border-karet/15 bg-pet/50 px-5 pb-6 pt-5 shadow-[0_2px_0_0_rgba(34,31,28,0.12)]">

    <h2 id="judul-timbangan" class="text-center text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-terpal/70">
        Tabungan sampahmu
    </h2>

    {{-- Muka timbangan gantung. aria-hidden: angkanya sudah dibacakan di bawah. --}}
    <div aria-hidden="true" class="mx-auto mt-3 w-full max-w-[280px]">
        <svg viewBox="0 0 200 116" class="w-full">
            {{-- busur skala --}}
            <path d="M18 104 A82 82 0 0 1 182 104" fill="none" stroke="var(--color-karet)" stroke-opacity="0.14" stroke-width="10" stroke-linecap="round"/>
            {{-- bagian terisi --}}
            <path d="M18 104 A82 82 0 0 1 182 104" fill="none" stroke="var(--color-terpal)" stroke-width="10" stroke-linecap="round"
                  stroke-dasharray="258" stroke-dashoffset="{{ round(258 * (1 - $rasio), 1) }}"/>

            {{-- garis skala tiap 1/5 --}}
            @for ($i = 0; $i <= 5; $i++)
                @php
                    $a = deg2rad(180 - ($i * 36));
                    $x1 = 100 + cos($a) * 68; $y1 = 104 - sin($a) * 68;
                    $x2 = 100 + cos($a) * 60; $y2 = 104 - sin($a) * 60;
                @endphp
                <line x1="{{ round($x1, 1) }}" y1="{{ round($y1, 1) }}" x2="{{ round($x2, 1) }}" y2="{{ round($y2, 1) }}"
                      stroke="var(--color-karet)" stroke-opacity="0.28" stroke-width="2" stroke-linecap="round"/>
            @endfor

            {{-- jarum --}}
            <g class="jarum" style="--sudut: {{ $sudut }}deg">
                <line x1="100" y1="104" x2="100" y2="34" stroke="var(--color-timbangan)" stroke-width="3.5" stroke-linecap="round"/>
                <circle cx="100" cy="34" r="3.5" fill="var(--color-timbangan)"/>
            </g>
            <circle cx="100" cy="104" r="7" fill="var(--color-karet)"/>
            <circle cx="100" cy="104" r="2.5" fill="var(--color-karung)"/>
        </svg>
    </div>

    <p class="-mt-1 text-center">
        <span class="angka block text-4xl font-bold leading-none text-karet">
            {{ number_format($beratKg, 1, ',', '.') }}<span class="ml-1 text-lg font-medium text-karet/55">kg</span>
        </span>
        <span class="angka mt-2 block text-xl font-semibold text-terpal">
            Rp {{ number_format($rupiah, 0, ',', '.') }}
        </span>
    </p>

    <p class="mt-1 text-center text-xs text-karet/50">total sejak kamu jadi nasabah</p>
</section>
