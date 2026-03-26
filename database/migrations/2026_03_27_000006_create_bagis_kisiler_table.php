<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bagis_kisiler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bagis_id')->constrained('bagislar')->cascadeOnDelete();
            $table->foreignId('kalem_id')->nullable()->constrained('bagis_kalemleri')->nullOnDelete();
            $table->foreignId('uye_id')->nullable()->constrained('uyeler')->nullOnDelete();
            $table->json('tip');
            $table->string('ad_soyad');
            $table->string('tc_kimlik', 11)->nullable();
            $table->string('telefon', 20)->nullable();
            $table->string('eposta', 255)->nullable();
            $table->tinyInteger('hisse_no')->nullable();
            $table->string('vekalet_ad_soyad', 255)->nullable();
            $table->string('vekalet_tc', 11)->nullable();
            $table->string('vekalet_telefon', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bagis_kisiler');
    }
};
