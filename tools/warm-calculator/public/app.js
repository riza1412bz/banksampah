// ==========================================================================
// WARM v16 Waste Report Application Logic (Full 61 Materials Database)
// ==========================================================================

const MATERIAL_CATALOG = [
  {
    "warm_name": "Aluminum Cans",
    "kode": "ALM",
    "nama": "Kaleng Minuman Aluminium (Aluminum Cans)",
    "grup": "Logam & Metal",
    "ef": 10.08,
    "ef_warm_pure": 10.08,
    "kwh": 49.44
  },
  {
    "warm_name": "Steel Cans",
    "kode": "BSI",
    "nama": "Besi / Kaleng Seng / Makanan (Steel Cans)",
    "grup": "Logam & Metal",
    "ef": 2.04,
    "ef_warm_pure": 2.04,
    "kwh": 6.54
  },
  {
    "warm_name": "Copper Wire",
    "kode": "TBG",
    "nama": "Kabel Tembaga (Copper Wire)",
    "grup": "Logam & Metal",
    "ef": 4.97,
    "ef_warm_pure": 4.97,
    "kwh": 26.77
  },
  {
    "warm_name": "Glass",
    "kode": "KCA",
    "nama": "Kaca / Botol & Wadah Kaca (Glass)",
    "grup": "Kaca",
    "ef": 0.33,
    "ef_warm_pure": 0.33,
    "kwh": 0.77
  },
  {
    "warm_name": "HDPE",
    "kode": "P02",
    "nama": "Plastik HDPE (Botol Sampo/Deterjen/Jerigen)",
    "grup": "Plastik & Polimer",
    "ef": 0.86,
    "ef_warm_pure": 0.86,
    "kwh": 14.55
  },
  {
    "warm_name": "LDPE",
    "kode": "P04",
    "nama": "Plastik LDPE (Kantong Kresek / Plastik Wrap)",
    "grup": "Plastik & Polimer",
    "ef": -1.4,
    "ef_warm_pure": -1.4,
    "kwh": 0.0
  },
  {
    "warm_name": "PET",
    "kode": "P14",
    "nama": "Plastik Rigid PET (Botol Bening Pasca-Konsumsi)",
    "grup": "Plastik & Polimer",
    "ef": 1.8,
    "ef_warm_pure": 1.16,
    "kwh": 9.32
  },
  {
    "warm_name": "Corrugated Containers",
    "kode": "K01",
    "nama": "Kardus / Karton Bergelombang (Corrugated)",
    "grup": "Kertas & Karton",
    "ef": 3.66,
    "ef_warm_pure": 3.66,
    "kwh": 4.81
  },
  {
    "warm_name": "Magazines/third-class mail",
    "kode": "K03",
    "nama": "Majalah / Kertas Berwarna / Buram",
    "grup": "Kertas & Karton",
    "ef": 2.91,
    "ef_warm_pure": 2.91,
    "kwh": 0.24
  },
  {
    "warm_name": "Newspaper",
    "kode": "K02",
    "nama": "Koran / Kertas Berita Bekas (Newspaper)",
    "grup": "Kertas & Karton",
    "ef": 2.05,
    "ef_warm_pure": 2.05,
    "kwh": 5.35
  },
  {
    "warm_name": "Office Paper",
    "kode": "K9",
    "nama": "Kertas / Dokumen Perkantoran Terpilah (HVS)",
    "grup": "Kertas & Karton",
    "ef": 1.2,
    "ef_warm_pure": 4.41,
    "kwh": 3.11
  },
  {
    "warm_name": "Phonebooks",
    "kode": "K05",
    "nama": "Buku Telepon / Direktori Kertas",
    "grup": "Kertas & Karton",
    "ef": 1.96,
    "ef_warm_pure": 1.96,
    "kwh": 3.88
  },
  {
    "warm_name": "Textbooks",
    "kode": "K04",
    "nama": "Buku Teks & Pelajaran (Textbooks)",
    "grup": "Kertas & Karton",
    "ef": 4.67,
    "ef_warm_pure": 4.67,
    "kwh": 0.18
  },
  {
    "warm_name": "Dimensional Lumber",
    "kode": "KS-WOD",
    "nama": "Kayu Solid Gergajian (Lumber Reuse)",
    "grup": "Konstruksi & Lainnya",
    "ef": 0.81,
    "ef_warm_pure": 0.81,
    "kwh": 1.77
  },
  {
    "warm_name": "Medium-density Fiberboard",
    "kode": "KS-MDF",
    "nama": "Papan Serat Kayu (MDF)",
    "grup": "Konstruksi & Lainnya",
    "ef": -0.3,
    "ef_warm_pure": -0.3,
    "kwh": 0.0
  },
  {
    "warm_name": "Structural Steel",
    "kode": "BJA",
    "nama": "Baja Struktural / Konstruksi",
    "grup": "Logam & Metal",
    "ef": 2.15,
    "ef_warm_pure": 2.15,
    "kwh": 3.08
  },
  {
    "warm_name": "Food Waste",
    "kode": "ORG-FD",
    "nama": "Sisa Makanan Umum (Food Waste)",
    "grup": "Organik & Makanan",
    "ef": 0.72,
    "ef_warm_pure": 0.72,
    "kwh": 0.0
  },
  {
    "warm_name": "Food Waste (non-meat)",
    "kode": "ORG-NM",
    "nama": "Sisa Makanan Nabati / Non-Daging",
    "grup": "Organik & Makanan",
    "ef": 0.72,
    "ef_warm_pure": 0.72,
    "kwh": 0.0
  },
  {
    "warm_name": "Food Waste (meat only)",
    "kode": "ORG-ME",
    "nama": "Sisa Daging & Lemak Hewani",
    "grup": "Organik & Makanan",
    "ef": 0.68,
    "ef_warm_pure": 0.68,
    "kwh": 0.0
  },
  {
    "warm_name": "Beef",
    "kode": "ORG-BF",
    "nama": "Sisa Daging Sapi (Beef)",
    "grup": "Organik & Makanan",
    "ef": 0.64,
    "ef_warm_pure": 0.64,
    "kwh": 0.0
  },
  {
    "warm_name": "Poultry",
    "kode": "ORG-PL",
    "nama": "Sisa Daging Unggas / Ayam (Poultry)",
    "grup": "Organik & Makanan",
    "ef": 0.71,
    "ef_warm_pure": 0.71,
    "kwh": 0.0
  },
  {
    "warm_name": "Grains",
    "kode": "ORG-GR",
    "nama": "Biji-bijian & Sereal (Grains)",
    "grup": "Organik & Makanan",
    "ef": 1.68,
    "ef_warm_pure": 1.68,
    "kwh": 0.0
  },
  {
    "warm_name": "Bread",
    "kode": "ORG-BR",
    "nama": "Roti & Kue Olahan Gandum (Bread)",
    "grup": "Organik & Makanan",
    "ef": 1.26,
    "ef_warm_pure": 1.26,
    "kwh": 0.0
  },
  {
    "warm_name": "Fruits and Vegetables",
    "kode": "ORG-FV",
    "nama": "Buah & Sayur-Sayuran",
    "grup": "Organik & Makanan",
    "ef": 0.42,
    "ef_warm_pure": 0.42,
    "kwh": 0.0
  },
  {
    "warm_name": "Dairy Products",
    "kode": "ORG-DY",
    "nama": "Produk Olahan Susu / Dairy",
    "grup": "Organik & Makanan",
    "ef": 0.7,
    "ef_warm_pure": 0.7,
    "kwh": 0.0
  },
  {
    "warm_name": "Yard Trimmings",
    "kode": "ORG-YD",
    "nama": "Sampah Kebun & Taman (Yard Trimmings)",
    "grup": "Organik & Makanan",
    "ef": -0.1,
    "ef_warm_pure": -0.1,
    "kwh": 0.0
  },
  {
    "warm_name": "Grass",
    "kode": "ORG-GS",
    "nama": "Rumput Potong (Grass)",
    "grup": "Organik & Makanan",
    "ef": 0.24,
    "ef_warm_pure": 0.24,
    "kwh": 0.0
  },
  {
    "warm_name": "Leaves",
    "kode": "ORG-LV",
    "nama": "Dedaunan Gugur (Leaves)",
    "grup": "Organik & Makanan",
    "ef": -0.47,
    "ef_warm_pure": -0.47,
    "kwh": 0.0
  },
  {
    "warm_name": "Branches",
    "kode": "ORG-BRN",
    "nama": "Ranting & Dahan Pohon (Branches)",
    "grup": "Organik & Makanan",
    "ef": -0.47,
    "ef_warm_pure": -0.47,
    "kwh": 0.0
  },
  {
    "warm_name": "Mixed Paper (general)",
    "kode": "K-MIX",
    "nama": "Campuran Kertas Umum (Mixed Paper)",
    "grup": "Kertas & Karton",
    "ef": 3.99,
    "ef_warm_pure": 3.99,
    "kwh": 6.59
  },
  {
    "warm_name": "Mixed Paper (primarily residential)",
    "kode": "K-RES",
    "nama": "Campuran Kertas Rumah Tangga",
    "grup": "Kertas & Karton",
    "ef": 3.92,
    "ef_warm_pure": 3.92,
    "kwh": 6.59
  },
  {
    "warm_name": "Mixed Paper (primarily from offices)",
    "kode": "K-OFF",
    "nama": "Campuran Kertas Perkantoran",
    "grup": "Kertas & Karton",
    "ef": 4.07,
    "ef_warm_pure": 4.07,
    "kwh": 6.69
  },
  {
    "warm_name": "Mixed Metals",
    "kode": "LGM-MIX",
    "nama": "Campuran Logam & Metal (Mixed Metals)",
    "grup": "Logam & Metal",
    "ef": 4.86,
    "ef_warm_pure": 4.86,
    "kwh": 21.59
  },
  {
    "warm_name": "Mixed Plastics",
    "kode": "PLS-MIX",
    "nama": "Campuran Plastik Anorganik (Mixed Plastics)",
    "grup": "Plastik & Polimer",
    "ef": 1.04,
    "ef_warm_pure": 1.04,
    "kwh": 11.4
  },
  {
    "warm_name": "Mixed Recyclables",
    "kode": "RCY-MIX",
    "nama": "Campuran Material Daur Ulang Umum",
    "grup": "Konstruksi & Lainnya",
    "ef": 3.13,
    "ef_warm_pure": 3.13,
    "kwh": 4.81
  },
  {
    "warm_name": "Mixed Organics",
    "kode": "ORG-MIX",
    "nama": "Campuran Bahan Organik",
    "grup": "Organik & Makanan",
    "ef": 0.32,
    "ef_warm_pure": 0.32,
    "kwh": 0.0
  },
  {
    "warm_name": "Mixed MSW",
    "kode": "MSW-MIX",
    "nama": "Campuran Sampah Kota Padat (MSW)",
    "grup": "Konstruksi & Lainnya",
    "ef": 0.33,
    "ef_warm_pure": 0.33,
    "kwh": 0.0
  },
  {
    "warm_name": "Carpet",
    "kode": "KS-CPT",
    "nama": "Karpet Lantai Bekas (Carpet)",
    "grup": "Konstruksi & Lainnya",
    "ef": 2.65,
    "ef_warm_pure": 2.65,
    "kwh": 7.02
  },
  {
    "warm_name": "Desktop CPUs",
    "kode": "EL-PC",
    "nama": "Komputer PC / Unit CPU Desktop",
    "grup": "Elektronik (E-Waste)",
    "ef": 1.66,
    "ef_warm_pure": 1.66,
    "kwh": 6.94
  },
  {
    "warm_name": "Portable Electronic Devices",
    "kode": "EL-NB",
    "nama": "Laptop / Tablet / Perangkat Portabel",
    "grup": "Elektronik (E-Waste)",
    "ef": 1.19,
    "ef_warm_pure": 1.19,
    "kwh": 6.85
  },
  {
    "warm_name": "Flat-Panel Displays",
    "kode": "EL-LCD",
    "nama": "Monitor Layar Datar (LCD/LED/OLED)",
    "grup": "Elektronik (E-Waste)",
    "ef": 1.12,
    "ef_warm_pure": 1.12,
    "kwh": 4.96
  },
  {
    "warm_name": "CRT Displays",
    "kode": "EL-CRT",
    "nama": "Monitor / TV Tabung CRT",
    "grup": "Elektronik (E-Waste)",
    "ef": 0.65,
    "ef_warm_pure": 0.65,
    "kwh": 2.65
  },
  {
    "warm_name": "Electronic Peripherals",
    "kode": "EL-ACC",
    "nama": "Aksesoris / Periferal Elektronik (Mouse/Keyboard)",
    "grup": "Elektronik (E-Waste)",
    "ef": 0.42,
    "ef_warm_pure": 0.42,
    "kwh": 2.63
  },
  {
    "warm_name": "Hard-Copy Devices",
    "kode": "EL-PRN",
    "nama": "Printer / Scanner / Mesin Fotokopi",
    "grup": "Elektronik (E-Waste)",
    "ef": 0.64,
    "ef_warm_pure": 0.64,
    "kwh": 2.64
  },
  {
    "warm_name": "Mixed Electronics",
    "kode": "EL-MIX",
    "nama": "Campuran Sampah Elektronik (E-Waste)",
    "grup": "Elektronik (E-Waste)",
    "ef": 1.02,
    "ef_warm_pure": 1.02,
    "kwh": 4.62
  },
  {
    "warm_name": "Clay Bricks",
    "kode": "KS-BT",
    "nama": "Batu Bata Merah (Clay Bricks)",
    "grup": "Konstruksi & Lainnya",
    "ef": 0.0,
    "ef_warm_pure": 0.0,
    "kwh": 0.0
  },
  {
    "warm_name": "Concrete",
    "kode": "KS-COR",
    "nama": "Puing Beton (Concrete)",
    "grup": "Konstruksi & Lainnya",
    "ef": 0.03,
    "ef_warm_pure": 0.03,
    "kwh": 0.12
  },
  {
    "warm_name": "Fly Ash",
    "kode": "KS-ASH",
    "nama": "Abu Terbang Batu Bara (Fly Ash)",
    "grup": "Konstruksi & Lainnya",
    "ef": 0.98,
    "ef_warm_pure": 0.98,
    "kwh": 1.63
  },
  {
    "warm_name": "Tires",
    "kode": "KS-BAN",
    "nama": "Ban Karet Bekas (Tires)",
    "grup": "Konstruksi & Lainnya",
    "ef": 0.44,
    "ef_warm_pure": 0.44,
    "kwh": 1.25
  },
  {
    "warm_name": "Asphalt Concrete",
    "kode": "KS-ASP",
    "nama": "Aspal Beton Jalan",
    "grup": "Konstruksi & Lainnya",
    "ef": 0.11,
    "ef_warm_pure": 0.11,
    "kwh": 0.48
  },
  {
    "warm_name": "Asphalt Shingles",
    "kode": "KS-SNG",
    "nama": "Genteng Sirap Aspal (Shingles)",
    "grup": "Konstruksi & Lainnya",
    "ef": 0.12,
    "ef_warm_pure": 0.12,
    "kwh": 0.87
  },
  {
    "warm_name": "Drywall",
    "kode": "KS-GPS",
    "nama": "Papan Gipsum / Drywall",
    "grup": "Konstruksi & Lainnya",
    "ef": -0.1,
    "ef_warm_pure": -0.1,
    "kwh": 0.93
  },
  {
    "warm_name": "Fiberglass Insulation",
    "kode": "KS-INS",
    "nama": "Peredam Panas Fiberglass",
    "grup": "Konstruksi & Lainnya",
    "ef": 0.0,
    "ef_warm_pure": 0.0,
    "kwh": 0.0
  },
  {
    "warm_name": "Vinyl Flooring",
    "kode": "KS-VNL",
    "nama": "Lantai Vinyl / PVC Flooring",
    "grup": "Konstruksi & Lainnya",
    "ef": 0.36,
    "ef_warm_pure": 0.36,
    "kwh": 0.0
  },
  {
    "warm_name": "Wood Flooring",
    "kode": "KS-FLR",
    "nama": "Lantai Kayu / Parquet",
    "grup": "Konstruksi & Lainnya",
    "ef": 3.11,
    "ef_warm_pure": 3.11,
    "kwh": 2.67
  },
  {
    "warm_name": "Aluminum Ingot",
    "kode": "ALM-ING",
    "nama": "Batangan / Potongan Aluminium (Ingot)",
    "grup": "Logam & Metal",
    "ef": 7.96,
    "ef_warm_pure": 7.96,
    "kwh": 36.87
  },
  {
    "warm_name": "PLA",
    "kode": "P09",
    "nama": "Bioplastik PLA (Asam Polilaktat)",
    "grup": "Plastik & Polimer",
    "ef": -1.67,
    "ef_warm_pure": -1.67,
    "kwh": 0.0
  },
  {
    "warm_name": "LLDPE",
    "kode": "P06",
    "nama": "Plastik LLDPE (Film Plastik Elastis)",
    "grup": "Plastik & Polimer",
    "ef": -1.4,
    "ef_warm_pure": -1.4,
    "kwh": 0.0
  },
  {
    "warm_name": "PP",
    "kode": "P05",
    "nama": "Plastik PP (Gelas Plastik / Tutup Botol)",
    "grup": "Plastik & Polimer",
    "ef": 0.9,
    "ef_warm_pure": 0.9,
    "kwh": 14.46
  },
  {
    "warm_name": "PS",
    "kode": "P07",
    "nama": "Plastik Polistirena / Styrofoam (PS)",
    "grup": "Plastik & Polimer",
    "ef": -1.8,
    "ef_warm_pure": -1.8,
    "kwh": 0.0
  },
  {
    "warm_name": "PVC",
    "kode": "P08",
    "nama": "Plastik PVC (Pipa / Kabel Selang)",
    "grup": "Plastik & Polimer",
    "ef": -0.71,
    "ef_warm_pure": -0.71,
    "kwh": 0.0
  }
];

let itemsState = [];

// Initialize
document.addEventListener('DOMContentLoaded', () => {
  setupEventListeners();
  loadPreset('pdf');
});

function setupEventListeners() {
  // Preset buttons
  document.getElementById('btn-preset-pdf').addEventListener('click', () => {
    setActivePresetButton('btn-preset-pdf');
    loadPreset('pdf');
  });

  document.getElementById('btn-preset-mixed').addEventListener('click', () => {
    setActivePresetButton('btn-preset-mixed');
    loadPreset('mixed');
  });

  document.getElementById('btn-preset-office').addEventListener('click', () => {
    setActivePresetButton('btn-preset-office');
    loadPreset('office');
  });

  // Form inputs change
  ['input-mitra', 'input-manifes', 'input-periode', 'input-fasilitas'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', updateMetadataView);
    }
  });

  // Add row button
  document.getElementById('btn-add-row').addEventListener('click', () => {
    addItemRow({ kode: 'P05', name: 'Plastik PP (Gelas Plastik / Tutup Botol)', ef: 0.90, volume: 10.0 });
    recalculate();
  });

  // Print button
  document.getElementById('btn-print-doc').addEventListener('click', () => {
    window.print();
  });

  // Reset button
  document.getElementById('btn-reset-form').addEventListener('click', () => {
    loadPreset('pdf');
  });
}

function setActivePresetButton(activeId) {
  document.querySelectorAll('.btn-preset').forEach(btn => btn.classList.remove('active'));
  document.getElementById(activeId).classList.add('active');
}

function loadPreset(type) {
  const container = document.getElementById('items-container');
  container.innerHTML = '';
  itemsState = [];

  if (type === 'pdf') {
    document.getElementById('input-mitra').value = '—';
    document.getElementById('input-manifes').value = '001/PNB/BSIL/VII/2026';
    document.getElementById('input-periode').value = 'Juli 2026';
    document.getElementById('input-fasilitas').value = 'Bank Sampah Indah Lestari';

    addItemRow({ kode: 'K9', name: 'Kertas / Dokumen Perkantoran Terpilah', ef: 1.20, volume: 37.7 });
    addItemRow({ kode: 'P14', name: 'Plastik Rigi PET (Botol Bening Pasca- Konsumsi)', ef: 1.80, volume: 51.2 });
  } else if (type === 'mixed') {
    document.getElementById('input-mitra').value = 'Komunitas Peduli Lingkungan RW 08';
    document.getElementById('input-manifes').value = '042/PNB/BSIL/VIII/2026';
    document.getElementById('input-periode').value = 'Agustus 2026';
    document.getElementById('input-fasilitas').value = 'Bank Sampah Indah Lestari';

    addItemRow({ kode: 'ALM', name: 'Kaleng Minuman Aluminium (Aluminum Cans)', ef: 10.08, volume: 12.5 });
    addItemRow({ kode: 'P14', name: 'Plastik Rigid PET (Botol Bening Pasca-Konsumsi)', ef: 1.80, volume: 45.0 });
    addItemRow({ kode: 'K01', name: 'Kardus / Karton Bergelombang (Corrugated)', ef: 3.66, volume: 60.0 });
    addItemRow({ kode: 'BSI', name: 'Besi / Kaleng Seng / Makanan (Steel Cans)', ef: 2.04, volume: 25.0 });
  } else if (type === 'office') {
    document.getElementById('input-mitra').value = 'PT Hijau Selaras Indonesia';
    document.getElementById('input-manifes').value = '088/CORP/BSIL/VIII/2026';
    document.getElementById('input-periode').value = 'Agustus 2026';
    document.getElementById('input-fasilitas').value = 'Bank Sampah Indah Lestari';

    addItemRow({ kode: 'K9', name: 'Kertas / Dokumen Perkantoran Terpilah (HVS)', ef: 1.20, volume: 120.0 });
    addItemRow({ kode: 'K01', name: 'Kardus / Karton Bergelombang (Corrugated)', ef: 3.66, volume: 85.0 });
    addItemRow({ kode: 'P14', name: 'Plastik Rigid PET (Botol Bening Pasca-Konsumsi)', ef: 1.80, volume: 30.0 });
  }

  updateMetadataView();
  recalculate();
}

function renderSelectOptions(selectedKode) {
  const groups = {};
  MATERIAL_CATALOG.forEach(m => {
    const g = m.grup || 'Lainnya';
    if (!groups[g]) groups[g] = [];
    groups[g].push(m);
  });

  let html = '';
  for (const [groupName, items] of Object.entries(groups)) {
    html += '<optgroup label="' + groupName + '">';
    items.forEach(m => {
      const isSelected = m.kode === selectedKode ? 'selected' : '';
      html += '<option value="' + m.kode + '" ' + isSelected + ' data-ef="' + m.ef + '" data-name="' + m.nama + '" data-kwh="' + m.kwh + '">[' + m.kode + '] ' + m.nama + '</option>';
    });
    html += '</optgroup>';
  }
  return html;
}

function addItemRow(data) {
  const rowId = 'row-' + Date.now() + '-' + Math.random().toString(36).substr(2, 4);
  const container = document.getElementById('items-container');

  const rowDiv = document.createElement('div');
  rowDiv.className = 'item-row';
  rowDiv.id = rowId;

  rowDiv.innerHTML = '<div class="item-row-header"><span class="item-badge" id="' + rowId + '-badge">' + data.kode + '</span><button type="button" class="btn-remove-row" title="Hapus item">&times; Hapus</button></div><div class="item-inputs-grid"><select class="input-material-select">' + renderSelectOptions(data.kode) + '</select><input type="number" step="0.1" min="0" class="input-vol" value="' + data.volume + '" placeholder="Kg"><input type="number" step="0.01" min="0" class="input-ef" value="' + data.ef + '" placeholder="EF"></div>';

  container.appendChild(rowDiv);

  const selectEl = rowDiv.querySelector('.input-material-select');
  const volEl = rowDiv.querySelector('.input-vol');
  const efEl = rowDiv.querySelector('.input-ef');
  const badgeEl = rowDiv.querySelector('.item-badge');
  const removeBtn = rowDiv.querySelector('.btn-remove-row');

  selectEl.addEventListener('change', () => {
    const selectedOpt = selectEl.options[selectEl.selectedIndex];
    efEl.value = selectedOpt.dataset.ef;
    badgeEl.textContent = selectedOpt.value;
    recalculate();
  });

  volEl.addEventListener('input', recalculate);
  efEl.addEventListener('input', recalculate);

  removeBtn.addEventListener('click', () => {
    rowDiv.remove();
    recalculate();
  });
}

function updateMetadataView() {
  const mitra = document.getElementById('input-mitra').value.trim() || '—';
  const manifes = document.getElementById('input-manifes').value.trim() || '—';
  const periode = document.getElementById('input-periode').value.trim() || '—';
  const fasilitas = document.getElementById('input-fasilitas').value.trim() || '—';

  document.getElementById('view-mitra').textContent = mitra;
  document.getElementById('view-manifes').textContent = manifes;
  document.getElementById('view-periode').textContent = periode;
  document.getElementById('view-fasilitas').textContent = fasilitas;
  document.getElementById('view-mitra-text').textContent = mitra === '—' ? 'Mitra' : mitra;
}

function recalculate() {
  const rows = document.querySelectorAll('.item-row');
  const tbody = document.getElementById('view-table-body');
  tbody.innerHTML = '';

  let totalVol = 0;
  let totalEmisi = 0;

  rows.forEach(row => {
    const selectEl = row.querySelector('.input-material-select');
    const volEl = row.querySelector('.input-vol');
    const efEl = row.querySelector('.input-ef');

    const selectedOpt = selectEl.options[selectEl.selectedIndex];
    const kode = selectedOpt.value;
    const name = selectedOpt.dataset.name;
    const vol = parseFloat(volEl.value) || 0;
    const ef = parseFloat(efEl.value) || 0;

    if (vol <= 0) return;

    const emisiItem = vol * ef;
    totalVol += vol;
    totalEmisi += emisiItem;

    const tr = document.createElement('tr');
    tr.innerHTML = '<td class="col-code">' + kode + '</td><td class="col-name">' + name + '</td><td class="col-vol">' + formatNumber(vol, 1) + '</td><td class="col-ef">' + formatNumber(ef, 2) + '</td><td class="col-emisi">' + formatNumber(emisiItem, 2) + ' kg CO2e</td>';
    tbody.appendChild(tr);
  });

  const totalVolStr = formatNumber(totalVol, 1) + ' KG';
  const totalEmisiStr = formatNumber(totalEmisi, 2) + ' kg CO2e';

  document.getElementById('view-card-volume').textContent = totalVolStr;
  document.getElementById('view-card-emisi').textContent = formatNumber(totalEmisi, 2) + ' KG';
  document.getElementById('view-total-vol').textContent = totalVolStr;
  document.getElementById('view-total-emisi').textContent = totalEmisiStr;

  document.getElementById('view-relatable-emisi').textContent = totalEmisiStr;

  const pohonCount = Math.round(totalEmisi / 22.9);
  document.getElementById('view-eq-pohon').textContent = pohonCount + ' pohon';

  const mobilKm = Math.round(totalEmisi * 4.0029);
  document.getElementById('view-eq-mobil').textContent = mobilKm + ' KM';

  const jamLampu = Math.round((totalEmisi / 0.85) * 100);
  const jamLampuFormatted = jamLampu >= 1000 ? Math.round(jamLampu / 1000) + 'K Jam' : jamLampu + ' Jam';
  document.getElementById('view-eq-lampu').textContent = jamLampuFormatted;
}

function formatNumber(num, decimals = 2) {
  return num.toLocaleString('id-ID', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  });
}
