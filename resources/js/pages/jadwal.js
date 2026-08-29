// resources/js/pages/jadwal.js — dari admin/jadwal.blade.php
(() => {
  'use strict';
  const pilih = document.getElementById('user_id');
  const lokasi = document.getElementById('lokasi');
  if (!pilih || !lokasi) return;
  const alamat = window.__ALAMAT_NASABAH__ || (() => {
    const el = document.getElementById('alamat-data');
    if (el) try { return JSON.parse(el.textContent); } catch {}
    return {};
  })();
  pilih.addEventListener('change', () => {
    const id = pilih.value;
    lokasi.value = (id && alamat[id]) ? alamat[id] : '';
  });
})();
