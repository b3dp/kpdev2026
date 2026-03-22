<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etkinlikler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yonetici_id')->constrained('yoneticiler');
            $table->string('baslik', 100);
            $table->string('seo_baslik', 60)->nullable();
            $table->string('slug', 100)->unique();
            $table->text('ozet')->nullable();
            $table->longText('aciklama')->nullable();
            $table->string('tip', 20)->default('fiziksel');
            $table->string('durum', 20)->default('taslak');
            $table->timestamp('baslangic_tarihi');
            $table->timestamp('bitis_tarihi')->nullable();
            $table->string('konum_ad', 255)->nullable();
            $table->string('konum_adres', 500)->nullable();
            $table->string('konum_il', 100)->nullable();
            $table->string('konum_ilce', 100)->nullable();
            $table->decimal('konum_lat', 10, 7)->nullable();
            $table->decimal('konum_lng', 10, 7)->nullable();
            $table->string('konum_place_id', 255)->nullable();
            $table->string('online_link')->nullable();
            $table->unsignedInteger('kontenjan')->nullable();
            $table->unsignedInteger('kayitli_kisi')->default(0);
            $table->string('gorsel_orijinal')->nullable();
            $table->string('gorsel_lg')->nullable();
            $table->string('gorsel_og')->nullable();
            $table->string('gorsel_sm')->nullable();
            $table->string('gorsel_mobil_lg')->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('robots', 30)->default('index');
            $table->string('canonical_url')->nullable();
            $table->boolean('ai_islendi')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('durum');
            $table->index('baslangic_tarihi');
            $table->index(['durum', 'baslangic_tarihi']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etkinlikler');
    }
};
