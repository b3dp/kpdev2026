<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekayit_ogrenci_bilgileri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kayit_id')->constrained('ekayit_kayitlar')->cascadeOnDelete();
            $table->string('ad_soyad');
            $table->string('tc_kimlik', 11);
            $table->string('dogum_yeri', 255)->nullable();
            $table->date('dogum_tarihi');
            $table->string('baba_adi', 255)->nullable();
            $table->string('anne_adi', 255)->nullable();
            $table->text('adres')->nullable();
            $table->string('ikamet_il', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekayit_ogrenci_bilgileri');
    }
};
