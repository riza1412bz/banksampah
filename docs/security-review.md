# Security Review — Bank Sampah Indah Lestari

Metodologi: [anthropics/claude-code-security-review](https://github.com/anthropics/claude-code-security-review)
(review ala senior security engineer; hanya temуan berkepercayaan tinggi & benar-benar dapat dieksploitasi;
false-positive difilter ketat: DoS, rate-limiting, secret-on-disk, lack-of-hardening, dan temuan di file dokumentasi dikecualikan).

- Tanggal: 2026-08-29
- Cakupan: kode aplikasi Laravel (controllers, models, services, jobs, middleware, routes, config, blade) + perubahan terbaru (Google Sheets sync, kode_nasabah, timestamp, migrasi CHECK).
- File ditinjau: ~20 (Auth/Admin/Nasabah/Setoran/Harga/Jadwal/Dashboard controllers, User/Setoran models, Pencatat/Laporan/GoogleSheets/PerhitunganDampak services, ExportSetoranJob, SyncSetoranToGoogleSheets, PastikanAdmin/SinkronkanAppUrl middleware, routes/web.php, SetoranPolicy, config/database.php, config/services.php, blade `{!! !!}`).

## Ringkasan

| Severity | Jumlah |
|---|--:|
| HIGH (kode aplikasi) | 0 |
| MEDIUM | 1 (receiver-side / eksternal) |
| Di bawah ambang (defense-in-depth) | 2 |

**Verdict:** Tidak ditemukan kerentanan HIGH/MEDIUM yang dapat dieksploitasi pada kode aplikasi Laravel dengan bar kepercayaan ≥0.8. Satu isu integritas MEDIUM ada di sisi penerima (Google Apps Script), di luar batas aplikasi.

---

## MEDIUM-1 — Webhook Apps Script tanpa verifikasi pengirim

- **Lokasi:** `docs/google-apps-script.gs` `doPost()` (Web App yang ter-deploy di Google), kategori: `missing_authentication` / integrity.
- **Confidence:** 0.8
- **Deskripsi:** `doPost(e)` menerima POST apa pun dan langsung `appendRow` ke sheet tanpa memverifikasi pengirim. Backend Laravel sudah mengirim field `secret` opsional (`GoogleSheetsSync::sendPayload`), tetapi Apps Script tidak memeriksanya.
- **Skenario eksploit:** Pihak yang mengetahui URL `/exec` (mis. bocor dari log, riwayat, atau share tak sengaja) dapat menyuntik/memalsukan baris transaksi ke Google Sheet, merusak integritas laporan. Tidak berdampak ke database aplikasi (sheet adalah salinan sekunder).
- **Rekomendasi:** Di `doPost`, tolak request yang `secret`-nya tidak cocok dengan sebuah nilai yang disimpan di Script Properties, mis.:
  ```javascript
  var expected = PropertiesService.getScriptProperties().getProperty('WEBHOOK_SECRET');
  if (!expected || data.secret !== expected) {
    return ContentService.createTextOutput(JSON.stringify({ status: "forbidden" }))
      .setMimeType(ContentService.MimeType.JSON);
  }
  ```
  lalu set `GOOGLE_SHEETS_WEBHOOK_SECRET` di Render agar backend mengirim nilai yang sama.
- **Catatan cakupan:** Aset yang rentan berjalan di Google (bukan server aplikasi), dan menurut metodologi, temuan pada file dokumentasi dikecualikan. Dicantumkan karena `.gs` di sini adalah salinan script produksi nyata.

---

## Di bawah ambang pelaporan (defense-in-depth, bukan kerentanan eksploitable)

**DID-1 — Nama file export dari input tak divalidasi (traversal saat tulis).** `SetoranController::exportSetoran()` menyusun `$namaFile` dari query `dari`/`sampai` yang tidak divalidasi, lalu `ExportSetoranJob` menyimpan ke `storage_path('app/private/exports/'.$namaFile)`. Sebuah `../` pada `dari` dapat menulis `.xlsx` di luar folder export. **Kenapa bukan temuan:** endpoint admin-only (`['auth','admin']`), ekstensi terpaksa `.xlsx`, konten bukan kode → bukan RCE, pelaku harus admin tepercaya. Baca-download sudah aman karena `downloadExport` memakai `basename()`. **Fix murah:** validasi `dari`/`sampai` sebagai `date` (samakan dengan `simpanSetoran`).

**DID-2 — Kredensial DB pernah ter-commit (git history).** Sudah diperbaiki (R1: fallback literal dihapus dari `config/database.php`) dan password Supabase sudah dirotasi. Menurut metodologi, secret-on-disk dikelola terpisah / dikecualikan. Dicantumkan sebagai sudah-teratasi.

---

## Yang sudah benar (observasi positif)

- **SQL injection:** tidak ada. Query lewat Eloquent/Query Builder dengan binding; `selectRaw` hanya berisi agregat statis/percabangan driver (`STRING_AGG`/`GROUP_CONCAT`); term pencarian di-`addcslashes` + di-bind (`LaporanSetoran::queryPeriode`); advisory lock `pg_advisory_xact_lock(hashtext(?))` ter-parameter.
- **XSS:** Blade auto-escape; empat `{!! !!}` hanya `json_encode(...)` di dalam `<script type="application/json">` (slash ter-escape default → tidak bisa breakout `</script>`).
- **Mass assignment:** tidak ada sink `$request->all()`/`create($request)`/`update($request)`; semua tulis pakai array hasil `validate()`.
- **Authorization:** semua rute `/admin/*` di belakang `['auth','admin']`; `nasabah.struk` diproteksi `SetoranPolicy` (`isAdmin || owner`) → tanpa IDOR; query nasabah selalu di-scope `$request->user()->id`.
- **Auth & kredensial:** password pakai cast `hashed`; login & registrasi `throttle:10,1`; generasi `kode_nasabah` race-safe (`User::buatNasabah` dalam transaksi).
- **Host header:** `SinkronkanAppUrl` hanya menerima host localhost/`*.trycloudflare.com`.
- **File & egress:** `downloadExport` pakai `basename()` (anti traversal baca); tidak ada `eval`/`unserialize`/`exec`/`system`; satu-satunya egress HTTP adalah webhook dari config (bukan SSRF — host/protокol tidak dikontrol pengguna).
