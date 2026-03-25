<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurumsal_sayfalar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ust_sayfa_id')->nullable()->constrained('kurumsal_sayfalar');
            $table->string('sablon', 20)->default('standart');
            $table->string('ad', 255);
            $table->string('slug', 255)->unique();
            $table->foreignId('kurum_id')->nullable()->constrained('kurumlar')->nullOnDelete();
            $table->longText('icerik')->nullable();
            $table->text('ozet')->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('robots', 30)->default('index');
            $table->string('canonical_url', 500)->nullable();
            $table->string('og_gorsel')->nullable();
            $table->string('banner_masaustu')->nullable();
            $table->string('banner_mobil')->nullable();
            $table->string('banner_orijinal')->nullable();
            $table->string('gorsel_lg')->nullable();
            $table->string('gorsel_og')->nullable();
            $table->string('gorsel_sm')->nullable();
            $table->string('gorsel_orijinal')->nullable();
            $table->string('video_embed_url')->nullable();
            $table->string('durum', 20)->default('taslak');
            $table->boolean('ai_islendi')->default(false);
            $table->unsignedSmallInteger('sira')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('durum');
            $table->index('ust_sayfa_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurumsal_sayfalar');
    }
};
