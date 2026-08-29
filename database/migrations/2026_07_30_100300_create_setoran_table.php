<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris = satu kali nasabah setor satu jenis plastik.
     *
     * KEPUTUSAN PENTING: harga_per_kg dan total_rupiah DIBEKUKAN di baris ini,
     * tidak di-join dari tabel harga. Harga plastik fluktuatif — kalau di-join,
     * struk lama ikut berubah saat harga naik dan angkanya jadi bohong.
     *
     * Berat disimpan dalam GRAM sebagai integer (bukan float kg) supaya tidak
     * ada galat pembulatan; uang disimpan rupiah bulat.
     */
    public function up(): void
    {
        Schema::create('setoran', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_bukti', 30)->unique();   // BSIL-260730-0001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kategori_sampah_id')->constrained('kategori_sampah')->restrictOnDelete();

            $table->unsignedInteger('berat_gram');          // 8200 = 8,2 kg
            $table->unsignedInteger('harga_per_kg');        // dibekukan saat setor
            $table->unsignedInteger('total_rupiah');        // dibekukan, hasil hitung

            $table->date('tanggal_setor');
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tanggal_setor']);
            $table->index('tanggal_setor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setoran');
    }
};
