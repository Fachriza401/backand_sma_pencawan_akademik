<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nis', 30)->unique();
            $table->string('nisn', 30)->nullable()->unique();
            $table->string('kelas', 30)->index();
            $table->string('angkatan', 10)->index();
            $table->date('tanggal_masuk');
            $table->date('target_tanggal_lulus')->nullable();
            $table->string('nama_wali');
            $table->string('hubungan_wali', 20)->default('Orang Tua');
            $table->string('telepon_utama', 30);
            $table->string('telepon_cadangan', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->string('status', 20)->default('Aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
