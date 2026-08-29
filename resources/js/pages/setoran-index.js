// resources/js/pages/setoran-index.js — ekstraksi dari admin/setoran-index.blade.php
// Optimasi: debounce 120ms, DocumentFragment, cache dataset, precompute lowerCase
(() => {
  'use strict';
  const input = document.getElementById('cari-nasabah');
  const saran = document.getElementById('cari-saran');
  const bersih = document.getElementById('cari-bersih');
  const kartuEls = document.querySelectorAll('ul[data-list="transaksi"] > li');
  const linkExport = document.getElementById('link-export');
  if (!input || !saran || kartuEls.length === 0) return;

  const kartu = [...kartuEls].map((li) => ({
    el: li,
    nama: li.dataset.nama || '',
    namaLower: (li.dataset.nama || '').toLowerCase(),
  }));

  const namaUnik = [...new Set(kartu.map((k) => k.nama))].sort((a, b) =>
    a.localeCompare(b, 'id', { sensitivity: 'base' })
  );
  const namaUnikLower = namaUnik.map((n) => n.toLowerCase());

  const termsOf = (q) => String(q).split(',').map((s) => s.trim().toLowerCase()).filter(Boolean);
  const termTerakhir = (q) => { const t = termsOf(q); return t.length ? t[t.length - 1] : ''; };

  const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

  const saranUntuk = (q) => {
    const ql = termTerakhir(q);
    if (!ql) return [];
    const awalan = [], kata = [], lain = [];
    const escaped = ql.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const reKata = new RegExp('\\s' + escaped);
    for (let i = 0; i < namaUnik.length; i++) {
      const nl = namaUnikLower[i];
      if (nl.startsWith(ql)) awalan.push(namaUnik[i]);
      else if (reKata.test(nl)) kata.push(namaUnik[i]);
      else if (nl.includes(ql)) lain.push(namaUnik[i]);
    }
    const sortId = (a, b) => a.localeCompare(b, 'id', { sensitivity: 'base' });
    return [...awalan.sort(sortId), ...kata.sort(sortId), ...lain.sort(sortId)].slice(0, 8);
  };

  const tandai = (nama, q) => {
    const i = nama.toLowerCase().indexOf(q.toLowerCase());
    if (i < 0) return escapeHtml(nama);
    return (
      escapeHtml(nama.slice(0, i)) +
      '<mark class="bg-transparent font-semibold text-terpal">' +
      escapeHtml(nama.slice(i, i + q.length)) +
      '</mark>' +
      escapeHtml(nama.slice(i + q.length))
    );
  };

  let saranRaf = 0;
  const tampilkanSaran = () => {
    if (saranRaf) cancelAnimationFrame(saranRaf);
    saranRaf = requestAnimationFrame(() => {
      const q = termTerakhir(input.value);
      if (!q) { saran.classList.add('hidden'); saran.replaceChildren(); return; }
      const hasil = saranUntuk(input.value);
      if (!hasil.length) {
        saran.innerHTML = '<li class="px-3 py-2 text-karet/45" role="option" aria-disabled="true">Tidak ada saran</li>';
        saran.classList.remove('hidden');
        return;
      }
      const frag = document.createDocumentFragment();
      for (const nama of hasil) {
        const li = document.createElement('li');
        li.setAttribute('role', 'option');
        li.tabIndex = -1;
        li.dataset.nilai = nama;
        li.className = 'cursor-pointer px-3 py-2 text-karet hover:bg-terpal/10 focus:bg-terpal/10 focus:outline-none';
        li.innerHTML = tandai(nama, q);
        frag.appendChild(li);
      }
      saran.replaceChildren(frag);
      saran.classList.remove('hidden');
    });
  };

  let filterRaf = 0;
  const filterKartu = () => {
    if (filterRaf) cancelAnimationFrame(filterRaf);
    filterRaf = requestAnimationFrame(() => {
      const raw = input.value.trim();
      const terms = termsOf(input.value);
      if (bersih) {
        bersih.classList.toggle('hidden', !raw);
        bersih.classList.toggle('flex', !!raw);
      }
      if (!terms.length) {
        for (const k of kartu) k.el.style.display = '';
        return;
      }
      for (const k of kartu) {
        const show = terms.some((t) => k.namaLower.includes(t));
        k.el.style.display = show ? '' : 'none';
      }
    });
  };

  const syncLinkExport = () => {
    if (!linkExport) return;
    try {
      const url = new URL(linkExport.href);
      url.searchParams.set('cari', input.value.trim());
      linkExport.href = url.toString();
    } catch {}
  };

  let debounceTimer = 0;
  const onInput = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { tampilkanSaran(); filterKartu(); syncLinkExport(); }, 120);
    // Instant filter untuk UX tanpa debounce panjang
    filterKartu();
  };

  input.addEventListener('input', onInput, { passive: true });
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      saran.classList.add('hidden');
      if (!input.value) input.blur();
    } else if (e.key === 'Enter') {
      e.preventDefault();
      saran.classList.add('hidden');
    }
  });
  saran.addEventListener('click', (e) => {
    const opsi = e.target.closest('li[data-nilai]');
    if (!opsi) return;
    const koma = input.value.lastIndexOf(',');
    input.value = koma === -1 ? opsi.dataset.nilai : input.value.slice(0, koma + 1) + ' ' + opsi.dataset.nilai;
    saran.classList.add('hidden');
    filterKartu();
    syncLinkExport();
    input.focus();
  });
  if (bersih) bersih.addEventListener('click', () => {
    input.value = '';
    saran.classList.add('hidden');
    filterKartu();
    syncLinkExport();
    input.focus();
  });
  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !saran.contains(e.target)) saran.classList.add('hidden');
  });
  input.addEventListener('focus', tampilkanSaran);

  filterKartu();
  syncLinkExport();
})();
