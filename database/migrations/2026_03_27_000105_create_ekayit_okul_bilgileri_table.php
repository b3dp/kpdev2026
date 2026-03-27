<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekayit_okul_bilgileri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kayit_id')->constrained('ekayit_kayitlar')->cascadeOnDelete();
            $table->string('okul_adi', 255)->nullable();
            $table->string('okul_numarasi', 50)->nullable();
            $table->string('sube', 10)->nullable();
            $table->text('not')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekayit_okul_bilgileri');
    }
};
