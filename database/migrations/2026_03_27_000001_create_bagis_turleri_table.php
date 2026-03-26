<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bagis_turleri', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('slug')->unique();
            $table->string('ozellik', 30)->default('normal');
            $table->string('fiyat_tipi', 20)->default('sabit');
            $table->decimal('fiyat', 10, 2)->nullable();
            $table->decimal('minimum_tutar', 10, 2)->nullable();
            $table->json('oneri_tutarlar')->nullable();
            $table->text('aciklama')->nullable();
            $table->text('hadis_ayet')->nullable();
            $table->string('gorsel_kare')->nullable();
            $table->string('gorsel_dikey')->nullable();
            $table->string('gorsel_yatay')->nullable();
            $table->string('gorsel_orijinal')->nullable();
            $table->string('video_yol')->nullable();
            $table->string('acilis_tipi', 20)->default('manuel');
            $table->tinyInteger('acilis_hicri_ay')->nullable();
            $table->tinyInteger('acilis_hicri_gun')->nullable();
            $table->tinyInteger('kapanis_hicri_ay')->nullable();
            $table->tinyInteger('kapanis_hicri_gun')->nullable();
            $table->time('kapanis_saat')->nullable();
            $table->boolean('kurban_modulu')->default(false);
            $table->boolean('aktif')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bagis_turleri');
    }
};
