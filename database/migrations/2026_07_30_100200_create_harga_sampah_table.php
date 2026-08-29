<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Harga plastik fluktuatif: disimpan sebagai daftar berperiode, bukan satu
     * nilai di tabel kategori. berlaku_sampai NULL = harga yang sedang aktif.
     */
    public function up(): void
    {
        Schema::create('harga_sampah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_sampah_id')->constrained('kategori_sampah')->cascadeOnDelete();
            $table->unsignedInteger('harga_per_kg');        // rupiah, bilangan bulat
            $table->date('berlaku_dari');
            $table->date('berlaku_sampai')->nullable();     // NULL = masih berlaku
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kategori_sampah_id', 'berlaku_dari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harga_sampah');
    }
};
