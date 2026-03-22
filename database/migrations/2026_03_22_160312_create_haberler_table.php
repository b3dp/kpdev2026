<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haberler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yonetici_id')->constrained('yoneticiler');
            $table->string('baslik', 60);
            $table->string('slug', 100)->unique();
            $table->text('ozet')->nullable();
            $table->longText('icerik')->nullable();
            $table->string('durum', 20)->default('taslak');
            $table->string('oncelik', 20)->default('normal');
            $table->foreignId('kategori_id')->nullable()->constrained('haber_kategorileri')->nullOnDelete();
            $table->boolean('manset')->default(false);
            $table->timestamp('yayin_tarihi')->nullable();
            $table->timestamp('yayin_bitis')->nullable();
            $table->unsignedBigInteger('goruntuleme')->default(0);
            $table->string('meta_description', 160)->nullable();
            $table->string('robots', 30)->default('index');
            $table->string('canonical_url')->nullable();
            $table->boolean('ai_islendi')->default(false);
            $table->boolean('ai_onay')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('durum');
            $table->index('yayin_tarihi');
            $table->index('kategori_id');
            $table->index(['durum', 'yayin_tarihi']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haberler');
    }
};
