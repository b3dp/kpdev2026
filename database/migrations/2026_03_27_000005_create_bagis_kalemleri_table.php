<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bagis_kalemleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bagis_id')->constrained('bagislar')->cascadeOnDelete();
            $table->foreignId('bagis_turu_id')->constrained('bagis_turleri');
            $table->smallInteger('adet')->default(1);
            $table->decimal('birim_fiyat', 10, 2);
            $table->decimal('toplam', 10, 2);
            $table->string('sahip_tipi', 20)->default('kendi');
            $table->boolean('vekalet_onay')->default(false);
            $table->foreignId('kurban_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bagis_kalemleri');
    }
};
