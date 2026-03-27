<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekayit_kayitlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sinif_id')->constrained('ekayit_siniflar');
            $table->foreignId('uye_id')->nullable()->constrained('uyeler')->nullOnDelete();
            $table->string('durum', 30)->default('beklemede');
            $table->text('durum_notu')->nullable();
            $table->foreignId('yonetici_id')->nullable()->constrained('yoneticiler')->nullOnDelete();
            $table->timestamp('durum_tarihi')->nullable();
            $table->smallInteger('yedek_sira')->nullable();
            $table->text('genel_not')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['sinif_id', 'durum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekayit_kayitlar');
    }
};
