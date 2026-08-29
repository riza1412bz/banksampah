# AGENTS.md — Aturan Proyek Bank Sampah Indah Lestari

## Dokumentasi Obsidian (WAJIB)

Setiap ada update apapun tentang web bank sampah (fitur baru, perbaikan bug, optimasi, redesign, migrasi DB, perubahan config), **langsung catat ke vault Obsidian**:

```
/Users/bachtiarzulkarnaens/Documents/Obsidian Vault/Projects/Web Bank Sampah.md
```

- Append section baru di akhir file dengan format `## 📝 Update <tanggal> — <judul>` berisi rincian perubahan (file yang disentuh + line, penyebab & solusi bug, hasil verifikasi test/build).
- Jangan buat file duplikat di vault; selalu update file `Web Bank Sampah.md` yang sama.
- Jika akses ditolak (TCC/EPERM), ingatkan user untuk memberi Full Disk Access pada terminal.

## Perintah Verifikasi

```bash
php artisan test      # seluruh suite harus passed sebelum dianggap selesai
npm run build         # setelah mengubah resources/js atau resources/css
php artisan view:clear && php artisan cache:clear   # setelah ubah blade/config cache
php artisan migrate --force                         # setelah buat migrasi baru
```

## Konvensi Proyek

- Laravel 13 / PHP 8.4 / Tailwind CSS v4 / Vite 8 / SQLite (WAL).
- Desain: monokrom zinc (`--color-karung #fbfbfa`, `--color-karet/terpal #18181b`), tombol primer `rounded-full bg-zinc-900`, kartu `rounded-2xl border-zinc-200 bg-white shadow-sm`.
- Query tanggal wajib sargable: `where('tanggal_setor','>=', $dari)` bukan `whereDate()`.
- Cache hanya simpan skalar/array — jangan model Eloquent (`serializable_classes=false` menyebabkan `__PHP_Incomplete_Class`).
- Kode nasabah: `BSIL-XXXX` (perorangan), `CORP-XXXX` (corporate), generate race-safe via `lockForUpdate`.
- Setiap query nasabah wajib difilter `Auth::id()`, tidak menerima `user_id` dari browser.
- **Supabase Project Binding (WAJIB)**: Proyek ini terkunci secara permanen ke Supabase Project:
  - **Project URL**: `https://ipwickemvyiwrnzhjilo.supabase.co`
  - **Project Ref**: `ipwickemvyiwrnzhjilo`
  - **Database Host**: `db.ipwickemvyiwrnzhjilo.supabase.co` (Port 5432, Database `postgres`, User `postgres`).
  - DILARANG menghubungkan atau memigrasikan data ke project Supabase selain ref `ipwickemvyiwrnzhjilo`.
