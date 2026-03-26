<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bagislar', function (Blueprint $table) {
            $table->id();
            $table->string('bagis_no', 20)->unique();
            $table->foreignId('sepet_id')->constrained('bagis_sepetler');
            $table->foreignId('uye_id')->nullable()->constrained('uyeler')->nullOnDelete();
            $table->string('durum', 30)->default('beklemede');
            $table->decimal('toplam_tutar', 10, 2);
            $table->string('odeme_saglayici', 20)->default('albaraka');
            $table->string('odeme_referans', 255)->nullable();
            $table->string('makbuz_yol')->nullable();
            $table->boolean('makbuz_gonderildi')->default(false);
            $table->boolean('kurban_aktarildi')->default(false);
            $table->timestamp('odeme_tarihi')->nullable();
            $table->timestamps();
            $table->index(['durum', 'odeme_tarihi']);
            $table->index('bagis_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bagislar');
    }
};
