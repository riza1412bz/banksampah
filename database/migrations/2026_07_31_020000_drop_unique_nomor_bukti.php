<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setoran', function (Blueprint $table) {
            // Satu nomor bukti bisa dipakai banyak baris (transaksi multi-item).
            $table->dropUnique('setoran_nomor_bukti_unique');
            $table->index('nomor_bukti');
        });
    }

    public function down(): void
    {
        Schema::table('setoran', function (Blueprint $table) {
            $table->dropIndex(['nomor_bukti']);
            $table->unique('nomor_bukti');
        });
    }
};
