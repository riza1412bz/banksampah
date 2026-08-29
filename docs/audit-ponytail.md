# Audit Ponytail — Bank Sampah Indah Lestari

Audit menyeluruh atas seluruh kode (kecuali `vendor/`, `node_modules/`, `.git/`, `storage/`).
Prioritas: integritas database untuk data nasabah dan tabungan nasabah, lalu privasi/keamanan
(termasuk fitur Google Sheets sync), lalu over-engineering ala Ponytail.

- Tanggal: 2026-08-29
- Baseline test: `php artisan test` → 79 passed (286 assertions)
- Baseline style: `./vendor/bin/pint --test` → 84 files, 25 style issues (pre-existing)
- Driver DB: SQLite (WAL) lokal, Supabase/Postgres produksi

Legenda tag over-engineering: `delete` (buang), `stdlib` (pakai pustaka standar),
`native` (pakai fitur platform), `yagni` (abstraksi tanpa kebutuhan), `shrink` (logika sama, lebih ringkas).
Legenda severity integritas/keamanan: `KRITIS`, `TINGGI`, `SEDANG`, `RENDAH`.

---

## 1. Integritas Data Nasabah

**N1 [TINGGI] Generasi `kode_nasabah` pada registrasi mandiri tidak transaksional.**
`AuthController::daftar()` memanggil `kodeNasabahBerikutnya()` yang menjalankan `lockForUpdate()`
**di luar** `DB::transaction`, lalu `User::create()` juga di luar transaksi
(`app/Http/Controllers/AuthController.php` ~L52-123). Di mode autocommit, `FOR UPDATE`
langsung dilepas sehingga tak memberi proteksi; dua registrasi paralel bisa menghitung kode sama.
Satu-satunya penyelamat adalah UNIQUE index `kode_nasabah` (race → `QueryException`/500, bukan duplikat diam).
Bandingkan `AdminNasabahController::simpanNasabah()` yang sudah benar (di dalam `DB::transaction`).

**N2 [SEDANG] Ceiling 9999 per prefix.** Urutan diparse dengan `substr($terakhir, -4)` + string
ordering `orderByDesc('kode_nasabah')`. Nasabah ke-10000 per prefix menghasilkan suffix 5 digit;
`substr(-4)` salah baca → tabrakan/reset urutan.

**N3 [SEDANG] Degradasi lock diam-diam.** `try/catch (\Throwable)` di sekitar `lockForUpdate`
jatuh ke query tanpa lock bila driver menolak klausa locking — menghilangkan proteksi race tanpa jejak
(`AuthController` ~L109-118, `AdminNasabahController` ~L55-61).

**N4 [RENDAH/ponytail] Duplikasi logika generasi kode** di dua controller. Prinsip ponytail: perbaiki
fungsi bersama sekali (pindahkan ke satu tempat, mis. `User`), agar tidak ada sibling yang tertinggal salah.

Positif: `email`/`nik`/`kode_nasabah` UNIQUE; NIK `digits:16` + unique; alamat terstruktur wajib di admin.

## 2. Integritas Tabungan & Setoran

**Verifikasi utama (positif):** Tidak ada tabel/kolom `saldo`/`tabungan`. Tabungan selalu diturunkan
sebagai `SUM(setoran.total_rupiah)` (`User::totalRupiah()`, `NasabahController::beranda()`,
`withSum` di daftar admin). Karena selalu dihitung dari data, **saldo tidak pernah drift** dari setoran.

**S1 [SEDANG] Tidak ada debit/koreksi/jejak audit.** Tak ada konsep penarikan; "tabungan" sebenarnya
akumulasi setoran, bukan saldo yang bisa ditarik. Tak ada jalur edit/hapus setoran di UI, jadi salah
input hanya bisa dikoreksi lewat akses DB langsung dan tanpa jejak audit. (Keputusan desain — dicatat, bukan bug.)

**S2 [RENDAH] Pembulatan per baris.** `Setoran::hitungTotal()` = `(int) round(gram*harga/1000)` per baris;
total struk multi-item = jumlah baris yang masing-masing sudah dibulatkan, bisa selisih beberapa rupiah
dari `round(total_gram*harga/1000)`. Wajar dan konsisten dengan angka tiap baris — didokumentasikan saja.

Positif: uang & berat disimpan integer (rupiah bulat, berat gram) → tak ada galat float pada uang;
frozen price benar (`harga_per_kg` & `total_rupiah` dibekukan per baris); scoping `Auth` kuat
(`NasabahController` filter id user, `SetoranPolicy`); query periode sargable (`>=`/`<=`).

## 3. Integritas DB-level, Lintas-driver & Keamanan

**SEC1 [KRITIS] Kredensial DB Supabase ter-hardcode.** `config/database.php` blok `supabase`
memakai fallback password literal dan host proyek nyata
(`env('SUPABASE_DB_PASSWORD', env('DB_PASSWORD', '<password>'))`, host `db.<ref>.supabase.co`).
Kredensial produksi berada di source-control. → hapus fallback, ambil murni dari env, rotasi password.

**SEC2 [TINGGI] Webhook bisa bocor ke lingkungan test.** `phpunit.xml` tidak meng-override
`GOOGLE_SHEETS_WEBHOOK_URL` dan `services.google_sheets.enabled` default `true`, sehingga URL webhook
dari `.env` terbaca saat test. Test saat ini aman karena memakai `Http::fake()`, tapi test baru yang
lupa mem-fake bisa POST data ke sheet live. → set `GOOGLE_SHEETS_WEBHOOK_URL=""` &
`GOOGLE_SHEETS_SYNC_ENABLED=false` di `phpunit.xml`.

**D1 [SEDANG] `setoran.user_id` `cascadeOnDelete`.** Menghapus baris user memusnahkan seluruh riwayat
setorannya di level DB. Satu-satunya penjaga adalah guard aplikasi `AdminNasabahController::destroyNasabah()`;
jalur lain (tinker, command, operasi bulk) bisa menghapus riwayat finansial tanpa peringatan.

**D2 [SEDANG] Tanpa CHECK constraint.** Tidak ada jaminan `berat_gram > 0`, `harga_per_kg > 0`,
`total_rupiah >= 0` di level DB; hanya divalidasi di PHP (service layer).

**D3 [SEDANG] `unsignedInteger` tidak portabel ke Postgres.** Postgres tak punya unsigned integer →
kolom menjadi signed `integer` di Supabase; nilai negatif mungkin di level DB bila ditulis di luar service.

**D4 [RENDAH] `whereDate()` non-sargable di `JadwalSetor`** (`scopeMendatang`/`scopeSudahLewat`) —
inkonsistensi dengan jalur setoran yang sargable. Tabel jadwal kecil, dampak minor.

Positif: seluruh FK `constrained()`; `PengaturHarga::ubah()` & `PencatatSetoran` transaksional;
cache hanya menyimpan skalar/id (bukan model Eloquent); `LaporanSetoran` sadar driver
(`STRING_AGG` pgsql vs `GROUP_CONCAT` sqlite); SQLite dikonfigurasi WAL + busy_timeout.

## 4. Fitur Google Sheets Sync

**G1 [TINGGI] Privasi — data nasabah ke pihak ketiga.** `GoogleSheetsSync::formatSetoran()` mengirim
`nama_nasabah`, `kode_nasabah`, `jenis_nasabah`, `harga_per_kg`, `total_rupiah`, emisi, `petugas`, `catatan`
ke webhook Google Apps Script. Tidak mengirim NIK/alamat/telepon/email (minimisasi sebagian — baik).
Perlu kejelasan: siapa yang bisa mengakses sheet, konsen nasabah, dan retensi data.

**G2 [TINGGI] `enabled` default `true` (opt-out).** `config/services.php`:
`'enabled' => env('GOOGLE_SHEETS_SYNC_ENABLED', true)`. Transmisi data ke pihak ketiga sebaiknya opt-in
(default `false`, diaktifkan eksplisit di `.env`).

**G3 [SEDANG] Webhook tanpa autentikasi.** Payload tak membawa shared secret/HMAC; siapa pun dengan URL
bisa menyuntik baris ke sheet, dan penerima tak bisa memverifikasi bahwa aplikasi ini pengirimnya.

**G4 [SEDANG] Fire-and-forget → potensi drift DB↔sheet.** Job `tries=2` lalu di-drop; tidak ada penanda
`synced_at` di `setoran`, jadi baris yang gagal terkirim tak terdeteksi. `sheets:sync --all` mengirim ulang
seluruh data → potensi duplikat di sheet (kecuali Apps Script men-dedupe berdasarkan `id`).

**G5 [RENDAH] Ketergantungan async pada `QUEUE_CONNECTION`.** `.env` memakai `database` + worker
(`composer dev`/`queue:listen`), jadi non-blocking sesuai klaim. Jika `QUEUE_CONNECTION=sync`, dispatch
berjalan di dalam request → redirect admin terblokir hingga timeout 8s saat Google lambat; jika worker mati,
sheet tertunda tanpa batas.

**Verifikasi (bukan temuan):** angka `emisi_terhindar_kg_co2e` (= `berat_kg × faktor_emisi_kg_co2e`)
**konsisten** dengan `PerhitunganDampak` yang juga membaca `faktor_emisi_kg_co2e`
(formula terdokumentasi `E = Σ(berat × EF)`). Tidak ada divergensi.

**Over-engineering (silang §5):** relasi duplikat `Setoran::nasabah()`≡`user()` dan `petugas()`≡`dicatatOleh()`;
payload `--test` menduplikasi 15 kolom `formatSetoran` (rawan drift); `allow_redirects=true` redundan
(Guzzle mengikuti redirect secara default); fallback `'Nasabah Anonim'`/`'Sampah Umum'` defensif untuk FK NOT NULL;
FQN inline `\App\Jobs\SyncSetoranToGoogleSheets` di controller.

Positif: fail-safe (timeout 8s + `try/catch`, tak melempar ke alur utama); job eager-load relasi (hindari N+1);
6 test memakai `Http::fake()`/`Queue::fake()`.

## 5. Over-engineering (seluruh kode)

Format: `<tag> <yang dipangkas>. <pengganti>. [path]`

- `yagni` Relasi duplikat `nasabah()` (≡ `user()`) & `petugas()` (≡ `dicatatOleh()`). Pilih satu penamaan. [app/Models/Setoran.php]
- `yagni` ~9 dari 11 kolom lifecycle `kelompok_sampah` (`ef_virgin`, `ef_current`, `forest_c_seq`, `energy_*`, `combustion_ef`, `energy_landfilling_ef`, dst.) ditulis seeder `ResetDanSinkronWarm` tapi tidak pernah dibaca aplikasi — hanya `ef_recycled` dipakai (`HargaController`). Sisanya spekulatif. [app/Models/KelompokSampah.php, database/migrations/*kelompok*]
- `shrink` `->withOptions(['allow_redirects' => true])` redundan (default Guzzle). Hapus. [app/Services/GoogleSheetsSync.php]
- `shrink` Payload uji `--test` menuliskan 15 kolom manual yang menduplikasi bentuk `formatSetoran`. Bangun dari satu sumber. [app/Console/Commands/SyncGoogleSheetsCommand.php]
- `yagni` Fallback `'Nasabah Anonim'`/`'Sampah Umum'` untuk relasi FK NOT NULL yang tak mungkin null. [app/Services/GoogleSheetsSync.php]
- `shrink` FQN inline `\App\Jobs\SyncSetoranToGoogleSheets` & `\Illuminate\Support\Facades\DB` → pakai `use`. [app/Http/Controllers/SetoranController.php, AdminNasabahController.php]
- `shrink` 25 isu gaya pre-existing (`single_quote`, `concat_space`, `no_unused_imports`, `fully_qualified_strict_types`, dst.) di 25 berkas. Jalankan `./vendor/bin/pint` sekali. [seluruh repo]

Catatan cakupan: `tools/warm-calculator` adalah alat dev terpisah (tidak di-referensi `app/`), bukan kode mati aplikasi.
`ExportSetoranJob` dipakai (`SetoranController::exportSetoran`), bukan kode mati.

`net: -~40 baris & 0 dep wajib dari item aman; potensi -~9 kolom DB bila kolom lifecycle dipangkas (butuh keputusan produk).`

---

## Backlog Remediasi Terurut

Urut berdasarkan risiko × dampak. Perbaikan mengikuti prinsip Ponytail: perubahan terkecil yang benar,
perbaiki fungsi bersama sekali, dan tinggalkan satu test yang membuktikannya.

| # | Prioritas | Temuan | Perbaikan minimal | Test pembukti |
|---|-----------|--------|-------------------|---------------|
| R1 | KRITIS | SEC1 kredensial DB ter-hardcode | Hapus fallback literal di blok `supabase`; ambil dari env; rotasi password di Supabase | Assert `config('database.connections.supabase.password')` null saat env kosong |
| R2 | TINGGI | N1/N3/N4 generasi kode_nasabah tak race-safe & duplikat | Satukan ke `User::kodeBerikutnya($jenis)` yang membungkus `DB::transaction`+`lockForUpdate`; pakai di `AuthController` & `AdminNasabahController` | Registrasi mandiri berurutan menghasilkan kode berurutan; kedua controller pakai jalur sama |
| R3 | TINGGI | G2 sync default opt-out | `enabled` default `false`; set `GOOGLE_SHEETS_SYNC_ENABLED=true` eksplisit di `.env`/`.env.example` | `isConfigured()` false saat flag tak di-set |
| R4 | TINGGI | SEC2 webhook bocor ke test | `phpunit.xml`: `GOOGLE_SHEETS_WEBHOOK_URL=""` + `GOOGLE_SHEETS_SYNC_ENABLED=false` | Test tanpa `Http::fake()` tidak mengirim HTTP |
| R5 | SEDANG | D2/D3 tanpa CHECK & unsigned tak portabel | Migrasi tambah CHECK `berat_gram>0`, `harga_per_kg>=0`, `total_rupiah>=0` (portabel SQLite+pgsql) | Insert nilai negatif ditolak DB |
| R6 | SEDANG | G3 webhook tanpa autentikasi | Sisipkan `secret` dari env ke payload (opsional); dokumentasikan verifikasi di sisi Apps Script | Payload memuat secret bila di-set |
| R7 | SEDANG | G4 drift DB↔sheet | Dokumentasikan `--all` sebagai rekonsiliasi; (opsi lanjutan: kolom `synced_at`) | — (dokumentasi) |
| R8 | RENDAH | §5 over-engineering aman | Konsolidasi relasi duplikat, hapus `allow_redirects`, rapikan import, bangun payload `--test` dari satu sumber | Suite tetap hijau |
| R9 | RENDAH | D1 cascade hapus riwayat | Pertahankan guard aplikasi; pertimbangkan `restrictOnDelete` (butuh rebuild tabel SQLite) | Hapus user bersetoran tetap ditolak |

Catatan: R1–R4 dan R8 diterapkan pada sesi ini (perubahan kecil, aman, bertes). R5 diterapkan bila migrasi
lintas-driver terbukti hijau di SQLite. R6/R7/R9 sebagian butuh keputusan produk / perubahan eksternal
(Apps Script) → diterapkan sebatas sisi aplikasi + dokumentasi.

---

## Status Penerapan Perbaikan (2026-08-29)

Verifikasi akhir: `php artisan test` → **84 passed (299 assertions)**; `npm run build` → sukses.

| # | Status | Ringkasan perubahan |
|---|--------|---------------------|
| R1 | ✅ Diterapkan | `config/database.php`: fallback password literal blok `supabase` dihapus (kini murni dari env). **Aksi manual wajib: rotasi password Supabase (sudah terekspos di git history) & set `SUPABASE_DB_PASSWORD` di environment Render.** |
| R2 | ✅ Diterapkan | `User::buatNasabah()` + `User::kodeNasabahTerkunci()` — generasi kode race-safe (satu transaksi) & terpusat. `AuthController::daftar()` dan `AdminNasabahController::simpanNasabah()` memakai jalur yang sama; helper duplikat dihapus. Test: `tests/Feature/KodeNasabahTest.php`. |
| R3 | ✅ Diterapkan | `config/services.php`: `google_sheets.enabled` default `false` (opt-in). `.env` diberi `GOOGLE_SHEETS_SYNC_ENABLED=true` agar sync live tetap jalan; `.env.example` didokumentasikan. Test: opt-in default. |
| R4 | ✅ Diterapkan | `phpunit.xml`: `GOOGLE_SHEETS_WEBHOOK_URL=""` + `GOOGLE_SHEETS_SYNC_ENABLED=false` → test tak pernah mengontak webhook live. |
| R5 | ✅ Diterapkan (pgsql) | Migrasi `2026_08_29_000000_add_check_constraints_to_setoran_table` — CHECK `berat_gram>0 AND harga_per_kg>=0 AND total_rupiah>=0`, khusus Postgres/Supabase (no-op di SQLite/MySQL). Batas: SQLite dev/test tak dukung `ADD CHECK` tanpa rebuild tabel; tak direbuild demi diff kecil, jadi jaminan ini aktif di produksi (pgsql), sementara dev/test bergantung validasi service layer. |
| R6 | ✅ Diterapkan (sisi app) | `GoogleSheetsSync::sendPayload()` menyisipkan `secret` dari `GOOGLE_SHEETS_WEBHOOK_SECRET` bila di-set. **Butuh perubahan eksternal**: Apps Script harus memverifikasi `secret` agar efektif. Test: payload menyertakan secret. |
| R7 | 📝 Didokumentasikan | Drift DB↔sheet: `sheets:sync --all` sebagai alat rekonsiliasi. Kolom `synced_at` sebagai opsi lanjutan (belum diterapkan — keputusan produk). |
| R8 | ✅ Diterapkan | Relasi `Setoran::nasabah()`/`petugas()` (alias duplikat) dihapus → seluruh kode konsisten pakai `user()`/`dicatatOleh()`. `allow_redirects` redundan dihapus. Payload `--test` dibangun dari `formatSetoran()` (anti-drift). FQN inline job di controller → `use`. |
| R9 | 📝 Didokumentasikan | `cascadeOnDelete` pada `setoran.user_id` tetap; guard aplikasi `destroyNasabah()` dipertahankan. `restrictOnDelete` butuh rebuild tabel SQLite → ditunda (risiko > manfaat saat ini). |

Sisa pre-existing (di luar cakupan perubahan ini, tetap sebagai backlog): 25 isu gaya `pint` di repo (jalankan `./vendor/bin/pint` sekali), dan ~9 kolom lifecycle `kelompok_sampah` yang spekulatif (butuh keputusan produk sebelum dipangkas).
