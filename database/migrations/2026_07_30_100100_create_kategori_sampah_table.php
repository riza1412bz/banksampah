<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_sampah', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();      // PET, PP, HDPE, KRESEK
            $table->string('nama');                     // "Botol PET bening"
            $table->text('keterangan')->nullable();     // "botol air mineral, label dilepas"
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_sampah');
    }
};
