<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_setor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // NULL = jadwal umum
            $table->date('tanggal');
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('lokasi')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['tanggal', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_setor');
    }
};
