# WARM v16 Calculator & Waste Report Prototype (Lokal)

Folder ini adalah modul prototype lokal terisolasi untuk menguji dan memvalidasi formula perhitungan **EPA WARM v16 (Waste Reduction Model)** dengan format output dokumen resmi **`WASTE REPORT.pdf` (Laporan Emisi Terhindar / EPR Carbon Impact Analytics)**.

> **Catatan:** Modul ini bersifat lokal (tidak mengganggu atau mendeploy ke produksi).

---

## 📁 Struktur Direktori

```
tools/warm-calculator/
├── data/
│   └── warm_v16_factors.json    # Database faktor emisi, energi, dan konversi EPA WARM v16 (61 material)
├── src/
│   └── WarmCalculator.php       # Engine kalkulator backend PHP
├── public/                      # Antarmuka web interaktif & live print preview A4 (2 Halaman)
│   ├── index.html
│   ├── style.css
│   └── app.js
├── cli-test.php                 # Unit test otomatis & verifikasi kesesuaian nilai PDF
└── README.md
```

---

## 🚀 Cara Menjalankan Secara Lokal

### 1. Menjalankan Unit Test CLI (Terminal)
Untuk memverifikasi perhitungan matematika terhadap data asli di `WASTE REPORT.pdf`:

```bash
php tools/warm-calculator/cli-test.php
```

**Hasil Verifikasi:**
- **Material 1**: Kertas Dokumen Terpilah (`K9`) $37.7\text{ kg} \times 1.20 = 45.24\text{ kg CO}_2\text{e}$
- **Material 2**: Botol PET Pasca-Konsumsi (`P14`) $51.2\text{ kg} \times 1.80 = 92.16\text{ kg CO}_2\text{e}$
- **Total Sampah**: $88.9\text{ KG}$
- **Total Emisi Terhindar**: $137.40\text{ kg CO}_2\text{e}$
- **Ekuivalensi Relatable Metrics**:
  - 🌳 **6 pohon** (Bibit pohon tumbuh 10 tahun)
  - 🚗 **550 KM** (Jarak tempuh perjalanan mobil bensin dipangkas)
  - 💡 **11K s.d. 59K Jam** (Penghematan daya lampu LED 10W)

---

### 2. Membuka Web Preview Interaktif di Browser
Kamu bisa membuka file HTML secara langsung di browser atau menjalankan server PHP lokal:

#### Opsi A: Server PHP Lokal
```bash
php -S localhost:8088 -t tools/warm-calculator/public
```
Lalu buka: **`http://localhost:8088`**

#### Opsi B: Buka Langsung File HTML
Buka file berikut di browser (Chrome / Safari / Edge):
`file:///Users/bachtiarzulkarnaens/Projects/banksampah/tools/warm-calculator/public/index.html`

---

## 🖨️ Fitur Web Preview
1. **Interactive Form**: Ubah nama mitra korporasi, no manifes, periode, serta tambah/hapus baris komoditas sampah.
2. **1-Click Presets**:
   - `Sample PDF Asli (K9 + P14)`
   - `Bank Sampah Campuran`
   - `Perkantoran (HVS + PET + Kardus)`
3. **Live 2-Page A4 Preview**: Tampilan visual halaman 1 & 2 yang identik dengan dokumen `WASTE REPORT.pdf`.
4. **Cetak / Simpan PDF**: Menggunakan format CSS Print A4 yang presisi sehingga saat dicetak (Ctrl+P / Cmd+P) menghasilkan PDF rapi 2 halaman tanpa distorsi.
