<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Faktor emisi konsolidasi EPA ReCon (kg CO2e/kg) per kategori,
     * dipakai rumus sederhana E_A = W × EF dan ekuivalensi bibit pohon (÷60).
     */
    public function up(): void
    {
        Schema::table('kategori_sampah', function (Blueprint $table) {
            $table->decimal('faktor_emisi_kg_co2e', 8, 4)->nullable()->after('kelompok_sampah_id');
        });
    }

    public function down(): void
    {
        Schema::table('kategori_sampah', function (Blueprint $table) {
            $table->dropColumn('faktor_emisi_kg_co2e');
        });
    }
};
