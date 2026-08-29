<?php

use App\Http\Controllers\AdminNasabahController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HargaController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\NasabahController;
use App\Http\Controllers\SetoranController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('masuk');
    }

    return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'nasabah.beranda');
});

// --- Tamu ---
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'formMasuk'])->name('masuk');
    Route::post('/masuk', [AuthController::class, 'masuk'])->middleware('throttle:10,1');
    Route::get('/daftar', [AuthController::class, 'formDaftar'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'daftar'])->middleware('throttle:10,1');
});

Route::post('/keluar', [AuthController::class, 'keluar'])->middleware('auth')->name('logout');

// --- Nasabah (dan admin, untuk struk) ---
Route::middleware('auth')->group(function () {
    Route::get('/tabunganku', [NasabahController::class, 'beranda'])->name('nasabah.beranda');
    Route::get('/hitung', [NasabahController::class, 'kalkulator'])->name('nasabah.kalkulator');
    Route::get('/jadwal', [JadwalController::class, 'untukNasabah'])->name('nasabah.jadwal');
    // Otorisasi per-setoran ada di NasabahController::struk() lewat SetoranPolicy.
    Route::get('/struk/{setoran}', [NasabahController::class, 'struk'])->name('nasabah.struk');
});

// --- Admin ---
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::get('/setoran/baru', [SetoranController::class, 'formSetoran'])->name('setoran.create');
    Route::post('/setoran', [SetoranController::class, 'simpanSetoran'])->name('setoran.store');
    Route::get('/setoran', [SetoranController::class, 'daftarSetoran'])->name('setoran.index');
    Route::get('/setoran/export', [SetoranController::class, 'exportSetoran'])->name('setoran.export');
    Route::get('/setoran/export/unduh', [SetoranController::class, 'downloadExport'])->name('setoran.export-download');
    // ===== Harga & kategori =====
    Route::get('/harga', [HargaController::class, 'daftarHarga'])->name('harga.index');
    Route::post('/harga/ubah', [HargaController::class, 'ubahHarga'])->name('harga.ubah');
    Route::post('/harga/init-default', [HargaController::class, 'initDefaultHarga'])->name('harga.init-default');
    Route::get('/harga/kategori/baru', [HargaController::class, 'formKategoriBaru'])->name('harga.kategori-baru');
    Route::get('/harga/kategori/{kategori}/edit', [HargaController::class, 'formEditKategori'])->name('harga.edit-kategori');
    Route::post('/harga/kategori', [HargaController::class, 'storeKategori'])->name('harga.store-kategori');
    Route::put('/harga/kategori/{kategori}', [HargaController::class, 'updateKategori'])->name('harga.update-kategori');
    Route::delete('/harga/kategori/{kategori}', [HargaController::class, 'destroyKategori'])->name('harga.destroy-kategori');

    Route::get('/jadwal', [JadwalController::class, 'index'])->name('jadwal.index');
    Route::post('/jadwal', [JadwalController::class, 'store'])->name('jadwal.store');
    Route::get('/jadwal/{jadwal}/edit', [JadwalController::class, 'edit'])->name('jadwal.edit');
    Route::put('/jadwal/{jadwal}', [JadwalController::class, 'update'])->name('jadwal.update');
    Route::delete('/jadwal/{jadwal}', [JadwalController::class, 'destroy'])->name('jadwal.destroy');

    Route::get('/nasabah', [AdminNasabahController::class, 'daftarNasabah'])->name('nasabah.index');
    Route::get('/nasabah/baru', [AdminNasabahController::class, 'formNasabah'])->name('nasabah.create');
    Route::post('/nasabah', [AdminNasabahController::class, 'simpanNasabah'])->name('nasabah.store');
    Route::get('/nasabah/{nasabah}/edit', [AdminNasabahController::class, 'formEditNasabah'])->name('nasabah.edit');
    Route::put('/nasabah/{nasabah}', [AdminNasabahController::class, 'updateNasabah'])->name('nasabah.update');
    Route::put('/nasabah/{nasabah}/reset-sandi', [AdminNasabahController::class, 'resetSandiNasabah'])->name('nasabah.reset-sandi');
    Route::put('/nasabah/{nasabah}/toggle-aktif', [AdminNasabahController::class, 'toggleAktifNasabah'])->name('nasabah.toggle-aktif');
    Route::delete('/nasabah/{nasabah}', [AdminNasabahController::class, 'destroyNasabah'])->name('nasabah.destroy');
});
