<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haber_ai_revizyonlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('haber_id')->constrained('haberler')->cascadeOnDelete();
            $table->foreignId('olusturan_yonetici_id')->nullable()->constrained('yoneticiler')->nullOnDelete();
            $table->string('islem_tipi', 50)->default('ai_imla_duzeltme');
            $table->string('model', 120)->nullable();

            $table->string('orijinal_baslik', 255)->nullable();
            $table->string('duzeltilmis_baslik', 255)->nullable();
            $table->longText('orijinal_icerik')->nullable();
            $table->longText('duzeltilmis_icerik')->nullable();
            $table->text('orijinal_ozet')->nullable();
            $table->text('duzeltilmis_ozet')->nullable();
            $table->text('orijinal_meta_description')->nullable();
            $table->text('duzeltilmis_meta_description')->nullable();
            $table->json('diff_ozeti_json')->nullable();

            $table->boolean('uygulandi_mi')->default(false);
            $table->boolean('geri_alindi_mi')->default(false);
            $table->timestamp('uygulandi_at')->nullable();
            $table->timestamp('geri_alindi_at')->nullable();
            $table->timestamps();

            $table->index(['haber_id', 'created_at']);
            $table->index(['haber_id', 'uygulandi_mi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haber_ai_revizyonlari');
    }
};