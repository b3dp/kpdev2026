<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurban_kisiler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurban_id')->constrained('kurban_kayitlar')->cascadeOnDelete();
            $table->foreignId('bagis_kisi_id')->constrained('bagis_kisiler')->cascadeOnDelete();
            $table->json('tip');
            $table->string('ad_soyad', 255);
            $table->string('tc_kimlik', 11)->nullable();
            $table->string('telefon', 20)->nullable();
            $table->string('eposta', 255)->nullable();
            $table->tinyInteger('hisse_no')->nullable();
            $table->string('vekalet_ad_soyad', 255)->nullable();
            $table->string('vekalet_tc', 11)->nullable();
            $table->string('vekalet_telefon', 20)->nullable();
            $table->timestamps();

            $table->index('hisse_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurban_kisiler');
    }
};