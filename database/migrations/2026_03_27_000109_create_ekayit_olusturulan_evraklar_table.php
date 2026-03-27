<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekayit_olusturulan_evraklar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kayit_id')->constrained('ekayit_kayitlar')->cascadeOnDelete();
            $table->foreignId('sablon_id')->constrained('ekayit_evrak_sablonlari');
            $table->string('dosya_yol');
            $table->timestamp('olusturulma_tarihi')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekayit_olusturulan_evraklar');
    }
};
