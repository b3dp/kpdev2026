<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odeme_hatalari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bagis_id')->constrained('bagislar')->cascadeOnDelete();
            $table->string('saglayici', 20);
            $table->string('hata_kodu', 100)->nullable();
            $table->string('hata_mesaji', 500)->nullable();
            $table->string('kart_son_haneler', 4)->nullable();
            $table->string('banka_adi', 255)->nullable();
            $table->decimal('tutar', 10, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odeme_hatalari');
    }
};
