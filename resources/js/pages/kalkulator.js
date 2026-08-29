// resources/js/pages/kalkulator.js — hitung manual + grafik harga area smooth
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

// ---------- Hitung manual (tetap) ----------
(() => {
  'use strict';
  const items = [...document.querySelectorAll('[data-item]')];
  if (items.length) {
    const hasil = document.getElementById('hasil');
    const totalBerat = document.getElementById('total-berat');
    const reset = document.getElementById('reset');
    const rupiah = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');
    const kgFmt = (n) => n.toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' kg';
    const nilaiBerat = (input) => Math.max(Number(String(input.value).replace(',', '.')) || 0, 0);
    const rows = items.map((li) => {
      const cek = li.querySelector('[data-checklist]');
      const inp = li.querySelector('[data-berat]');
      const sub = li.querySelector('[data-subtotal]');
      const harga = Number(li.dataset.harga) || Number(inp?.dataset.harga) || 0;
      if (inp) inp.dataset.harga = String(harga);
      return { li, cek, inp, sub, harga };
    });
    let raf = 0;
    const scheduleHitung = () => {
      if (raf) cancelAnimationFrame(raf);
      raf = requestAnimationFrame(hitung);
    };
    function hitung() {
      let total = 0, berat = 0;
      for (const r of rows) {
        const on = r.cek.checked && nilaiBerat(r.inp) > 0;
        const b = on ? nilaiBerat(r.inp) : 0;
        const subtotal = b * r.harga;
        r.li.classList.toggle('opacity-40', !on);
        if (r.sub) r.sub.textContent = on ? rupiah(subtotal) : '—';
        total += subtotal;
        berat += b;
      }
      if (hasil) hasil.textContent = rupiah(total);
      if (totalBerat) totalBerat.textContent = kgFmt(berat);
    }
    for (const r of rows) {
      r.cek.addEventListener('change', scheduleHitung);
      if (r.inp) r.inp.addEventListener('input', scheduleHitung, { passive: true });
    }
    if (reset) reset.addEventListener('click', () => {
      for (const r of rows) { r.cek.checked = false; r.inp.value = ''; }
      scheduleHitung();
    });
    hitung();
  }
})();

// ---------- Grafik harga — Area Smooth + Crosshair + Filter + Dropdown ----------
(() => {
  const canvas = document.getElementById('chart-harga');
  const dataEl = document.getElementById('chart-data');
  const sel1 = document.getElementById('chart-kategori-1');
  const sel2 = document.getElementById('chart-kategori-2');
  const info = document.getElementById('chart-info');
  if (!canvas || !dataEl || !sel1) return;

  let rawData = {};
  try { rawData = JSON.parse(dataEl.textContent || '{}'); } catch { rawData = {}; }

  // Crosshair plugin: garis vertikal biru + dot saat hover
  const crosshairPlugin = {
    id: 'crosshair',
    afterDraw(chart) {
      const active = chart.tooltip?.getActiveElements?.();
      if (!active || !active.length) return;
      const ctx = chart.ctx;
      const x = active[0].element.x;
      const topY = chart.scales.y.top;
      const bottomY = chart.scales.y.bottom;
      ctx.save();
      ctx.beginPath();
      ctx.moveTo(x, topY);
      ctx.lineTo(x, bottomY);
      ctx.lineWidth = 1;
      ctx.strokeStyle = '#3b82f6';
      ctx.setLineDash([4, 4]);
      ctx.stroke();
      ctx.restore();
      // Dot sudah di-handle via pointHoverRadius
    }
  };
  Chart.register(crosshairPlugin);

  const fmtRp = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
  const fmtTgl = (s) => {
    try { const d = new Date(s); return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }); } catch { return s; }
  };

  let currentRange = 7; // default 1 minggu
  let chart = null;

  function cutoffDate(rangeDays) {
    if (!rangeDays || rangeDays === 0) return null;
    const d = new Date();
    d.setDate(d.getDate() - rangeDays);
    d.setHours(0,0,0,0);
    return d;
  }

  function pointsFor(kategoriId, rangeDays) {
    const entry = rawData[String(kategoriId)];
    if (!entry) return [];
    let pts = entry.points || [];
    const cutoff = cutoffDate(rangeDays);
    if (cutoff) {
      pts = pts.filter(p => {
        const pd = new Date(p.x);
        return pd >= cutoff;
      });
      // Jika setelah filter kosong tapi ada data, ambil titik terakhir agar garis tidak hilang
      if (!pts.length && entry.points.length) {
        pts = [entry.points[entry.points.length - 1]];
      }
    }
    // Pastikan ada minimal 2 titik untuk smooth curve: duplikasi titik terakhir dengan tanggal hari ini jika hanya 1
    if (pts.length === 1) {
      const last = pts[0];
      const today = new Date().toISOString().slice(0,10);
      if (last.x !== today) {
        pts = [...pts, { x: today, y: last.y }];
      }
    }
    return pts;
  }

  // Warna per garis — index 0 = dropdown 1, index 1 = dropdown 2
  const WARNA = [
    { border: '#18181b', fill: 'rgba(24,24,27,0.08)', point: '#18181b' },
    { border: '#3b82f6', fill: 'rgba(59,130,246,0.12)', point: '#3b82f6' },
  ];

  /**
   * Bangun sumbu waktu BERSAMA dari gabungan tanggal kedua seri yang dipilih,
   * urut kronologis. Setiap dataset dipetakan ke sumbu itu (null di tanggal
   * yang tidak dia punya) sehingga kedua garis selalu sejajar kiri→kanan
   * sesuai tanggal nyata, bukan berdiri sendiri-sendiri.
   */
  function buildChartData() {
    const ids = [sel1.value, sel2?.value || ''].filter(Boolean);
    const series = ids.map((id) => ({
      id,
      nama: rawData[String(id)]?.nama || 'Kategori',
      pts: pointsFor(id, currentRange),
    }));

    // Gabung semua tanggal unik dari kedua seri, urut naik
    const tanggal = [...new Set(series.flatMap((s) => s.pts.map((p) => p.x)))].sort();

    const datasets = series.map((s, i) => {
      const c = WARNA[i % WARNA.length];
      return {
        label: s.nama,
        // y sejajar dengan labels: null jika kategori ini tidak punya titik di tanggal itu
        data: tanggal.map((d) => s.pts.find((p) => p.x === d)?.y ?? null),
        borderColor: c.border,
        backgroundColor: c.fill,
        fill: true,
        tension: 0.4,
        borderWidth: 2,
        pointRadius: 0,
        pointHoverRadius: 5,
        pointBackgroundColor: c.point,
        pointHoverBorderColor: '#fff',
        pointBorderWidth: 2,
        spanGaps: true,
      };
    });

    return { labels: tanggal.map(fmtTgl), datasets };
  }

  function updateInfo() {
    if (!info) return;
    const id1 = sel1.value;
    const e1 = rawData[String(id1)];
    const pts = pointsFor(id1, currentRange);
    const rangeLabel = document.querySelector('.chart-range.bg-zinc-900')?.textContent?.trim() || '';
    const last = pts[pts.length - 1];
    info.textContent = e1 ? `${pts.length} titik · ${rangeLabel} · terakhir ${last ? fmtRp(last.y) : '-'}` : '';
  }

  function render() {
    const { labels, datasets } = buildChartData();

    if (!chart) {
      const ctx = canvas.getContext('2d');
      chart = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { intersect: false, mode: 'index' },
          plugins: {
            legend: { display: datasets.length > 1, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { size: 11 } } },
            tooltip: {
              backgroundColor: '#18181b',
              titleColor: '#fff',
              bodyColor: '#fff',
              padding: 10,
              cornerRadius: 10,
              displayColors: true,
              callbacks: {
                title: (items) => items[0]?.label || '',
                label: (item) => (item.parsed.y == null ? null : `${item.dataset.label}: ${fmtRp(item.parsed.y)} /kg`),
              },
            },
            crosshair: true,
          },
          scales: {
            x: {
              grid: { display: false },
              border: { display: false },
              ticks: { color: '#71717a', font: { size: 11 }, maxTicksLimit: 6, maxRotation: 0 },
            },
            y: {
              beginAtZero: false,
              grid: { color: '#f4f4f5' },
              border: { display: false },
              ticks: { color: '#71717a', font: { size: 11 }, callback: (v) => 'Rp ' + Number(v).toLocaleString('id-ID') },
            },
          },
          animation: { duration: 300 },
        },
      });
    } else {
      chart.data.labels = labels;
      chart.data.datasets = datasets;
      chart.options.plugins.legend.display = datasets.length > 1;
      chart.update('none');
    }
    updateInfo();
  }

  // Event: dropdown per kategori
  sel1.addEventListener('change', render);
  if (sel2) sel2.addEventListener('change', () => {
    // Cegah pilih kategori sama di kedua dropdown
    if (sel2.value && sel2.value === sel1.value) {
      sel2.value = '';
    }
    render();
  });

  // Event: segmented control waktu
  document.querySelectorAll('.chart-range').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.chart-range').forEach(b => {
        b.classList.remove('bg-zinc-900', 'text-white');
        b.classList.add('text-zinc-600');
      });
      btn.classList.remove('text-zinc-600');
      btn.classList.add('bg-zinc-900', 'text-white');
      currentRange = parseInt(btn.dataset.range || '0', 10) || 0;
      render();
    });
  });

  // Initial
  render();

  // Update saat kategoriOptions berubah (jika ada live update)
  window.addEventListener('resize', () => chart?.resize());
})();
