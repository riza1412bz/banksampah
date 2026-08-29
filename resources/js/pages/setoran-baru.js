// resources/js/pages/setoran-baru.js — dipisah dari blade untuk cache + minify + defer (Vite)
// Menggantikan inline <script> di admin/setoran-baru.blade.php
(() => {
  'use strict';

  const checkboxes = document.querySelectorAll('.cek-item');
  if (!checkboxes.length) return;

  const beratInputs = document.querySelectorAll('.berat-item');
  const campurHarga = document.getElementById('campur_harga');
  const panelCampur = document.getElementById('panel-campur');
  const pratinjau = document.getElementById('pratinjau');
  const pratinjauBerat = document.getElementById('pratinjau-berat');
  const dampakGhg = document.getElementById('dampak-ghg');
  const dampakPohon = document.getElementById('dampak-pohon');
  const dampakRincian = document.getElementById('dampak-rincian');

  // Deteksi id Campur sekali di awal (hindari loop per hitung)
  let campurId = null;
  for (const cb of checkboxes) {
    const label = cb.closest('.mb-2')?.querySelector('.font-medium')?.textContent.trim() || '';
    if (cb.dataset.kode === 'CAMPUR' || label.toLowerCase().includes('campur')) {
      campurId = cb.dataset.id;
      break;
    }
  }

  // Cache mapping id → {cb, inp, wrapper, hargaDefault, ghg, kelompok}
  const items = [...checkboxes].map((cb) => {
    const wrapper = cb.closest('.mb-2');
    const inp = wrapper?.querySelector('.berat-item');
    return {
      cb,
      inp,
      wrapper,
      hargaDefault: Number(cb.dataset.harga) || 0,
      ghg: Number(cb.dataset.ghg) || 0,
      kelompok: cb.dataset.kelompok || '',
    };
  });

  const toggleCampurPanel = () => {
    if (!campurId || !panelCampur) return;
    const cb = document.querySelector(`.cek-item[data-id="${campurId}"]`);
    panelCampur.classList.toggle('hidden', !cb?.checked);
  };

  const toggleBerat = () => {
    for (const { cb, inp } of items) {
      if (!inp) continue;
      const on = !!cb.checked;
      inp.disabled = !on;
      if (!on) inp.value = '';
    }
  };

  let raf = 0;
  const scheduleHitung = () => {
    if (raf) cancelAnimationFrame(raf);
    raf = requestAnimationFrame(() => {
      hitungTotal();
      hitungDampak();
    });
  };

  const parseKg = (v) => Math.max(Number(String(v || '0').replace(',', '.')) || 0, 0);

  function hitungTotal() {
    let total = 0;
    let totalBerat = 0;
    const customHarga = campurHarga ? Math.max(Number(campurHarga.value) || 0, 0) : 0;
    const pakaiCustom = campurId && panelCampur && !panelCampur.classList.contains('hidden');

    for (const { cb, inp, hargaDefault } of items) {
      if (!cb.checked) continue;
      const kg = parseKg(inp?.value);
      if (kg <= 0) continue;
      totalBerat += kg;
      let harga = hargaDefault;
      if (pakaiCustom && cb.dataset.id === campurId) harga = customHarga;
      total += Math.round(kg * harga);
    }
    if (pratinjau) pratinjau.textContent = 'Rp ' + total.toLocaleString('id-ID');
    if (pratinjauBerat) pratinjauBerat.textContent = totalBerat.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' kg';
  }

  function hitungDampak() {
    if (!dampakGhg || !dampakPohon || !dampakRincian) return;
    let totalGhg = 0;
    const perKelompok = new Map();
    for (const { cb, inp, ghg, kelompok } of items) {
      if (!cb.checked) continue;
      const kg = parseKg(inp?.value);
      if (kg <= 0 || ghg <= 0) continue;
      const cur = kg * ghg;
      totalGhg += cur;
      if (kelompok) {
        const prev = perKelompok.get(kelompok) || 0;
        perKelompok.set(kelompok, prev + cur);
      }
    }
    dampakGhg.textContent = formatDampak(totalGhg);
    dampakPohon.textContent = formatDampak(totalGhg / 60);

    // Render rincian via DocumentFragment (hindari reflow per li)
    const frag = document.createDocumentFragment();
    const urut = [...perKelompok.entries()].sort((a, b) => b[1] - a[1]);
    for (const [nama, ghg] of urut) {
      if (ghg <= 0) continue;
      const li = document.createElement('li');
      li.className = 'flex items-baseline justify-between gap-2';
      li.innerHTML =
        '<span class="font-medium text-karet/70">' +
        escapeHtml(nama) +
        '</span><span class="angka tabular-nums text-karet/60">' +
        formatDampak(ghg) +
        ' CO₂e · ' +
        formatDampak(ghg / 60) +
        ' pohon</span>';
      frag.appendChild(li);
    }
    dampakRincian.replaceChildren(frag);
  }

  function formatDampak(n) {
    if (!n || n <= 0) return '0';
    const bulat = Number.isInteger(n) ? n : Math.round(n * 100) / 100;
    return bulat.toLocaleString('id-ID', { maximumFractionDigits: 2 });
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
  }

  for (const { cb } of items) cb.addEventListener('change', () => { toggleBerat(); toggleCampurPanel(); scheduleHitung(); });
  for (const { inp } of items) if (inp) inp.addEventListener('input', scheduleHitung, { passive: true });
  if (campurHarga) campurHarga.addEventListener('input', scheduleHitung, { passive: true });

  toggleBerat();
  toggleCampurPanel();
  hitungTotal();
  hitungDampak();
})();
