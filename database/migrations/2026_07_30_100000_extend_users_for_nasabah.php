<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('nasabah')->after('email');
            $table->string('kode_nasabah')->nullable()->unique()->after('role');
            $table->string('telepon', 20)->nullable()->after('kode_nasabah');
            $table->text('alamat')->nullable()->after('telepon');
            $table->boolean('aktif')->default(true)->after('alamat');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'kode_nasabah', 'telepon', 'alamat', 'aktif']);
        });
    }
};
