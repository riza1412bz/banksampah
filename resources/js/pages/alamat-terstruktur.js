// resources/js/pages/alamat-terstruktur.js — dari admin/_alamat-terstruktur.blade.php
// Dropdown Kota → Kecamatan → Desa. WILAYAH di-inject via window.__WILAYAH__ oleh blade.
(() => {
  'use strict';
  const kota = document.getElementById('kota');
  const kecamatan = document.getElementById('kecamatan');
  const desa = document.getElementById('desa_kelurahan');
  if (!kota || !kecamatan || !desa) return;

  // Blade menyisipkan: <script>window.__WILAYAH__ = @json($wilayah)</script> sebelum file ini
  const WILAYAH = window.__WILAYAH__ || (() => {
    const el = document.getElementById('wilayah-data');
    if (el) try { return JSON.parse(el.textContent); } catch {}
    return {};
  })();

  const isi = (el, daftar, placeholder) => {
    const frag = document.createDocumentFragment();
    const kosong = document.createElement('option');
    kosong.value = ''; kosong.textContent = placeholder; kosong.selected = true; kosong.disabled = true;
    frag.appendChild(kosong);
    for (const item of daftar) {
      const o = document.createElement('option');
      o.value = item; o.textContent = item;
      frag.appendChild(o);
    }
    el.replaceChildren(frag);
    el.disabled = false;
  };

  const muatKecamatan = (pilih = '') => {
    const k = kota.value;
    if (!k || !WILAYAH[k]) {
      isi(kecamatan, [], 'Pilih kota dulu');
      isi(desa, [], 'Pilih kecamatan dulu');
      kecamatan.disabled = true; desa.disabled = true;
      return;
    }
    isi(kecamatan, Object.keys(WILAYAH[k]), 'Pilih kecamatan');
    if (pilih) kecamatan.value = pilih;
    muatDesa(window.__DESA_PILIH__ || '');
  };

  const muatDesa = (pilih = '') => {
    const k = kota.value, c = kecamatan.value;
    if (!k || !c || !WILAYAH[k]?.[c]) {
      isi(desa, [], 'Pilih kecamatan dulu');
      desa.disabled = true; return;
    }
    isi(desa, WILAYAH[k][c], 'Pilih desa/kelurahan');
    if (pilih) desa.value = pilih;
  };

  kota.addEventListener('change', () => muatKecamatan());
  kecamatan.addEventListener('change', () => muatDesa());

  // Prefill saat edit — nilai disimpan di window oleh blade
  const kotaAwal = kota.value;
  if (kotaAwal && WILAYAH[kotaAwal]) {
    muatKecamatan(window.__KEC_PILIH__ || '');
    muatDesa(window.__DESA_PILIH__ || '');
  } else {
    kecamatan.disabled = true; desa.disabled = true;
  }
})();
