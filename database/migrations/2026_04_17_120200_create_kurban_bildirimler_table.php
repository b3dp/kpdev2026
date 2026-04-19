<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurban_bildirimler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurban_id')->constrained('kurban_kayitlar')->cascadeOnDelete();
            $table->foreignId('kurban_kisi_id')->constrained('kurban_kisiler')->cascadeOnDelete();
            $table->string('kanal', 20);
            $table->string('alici_ad', 255);
            $table->string('alici_iletisim', 255);
            $table->string('durum', 20);
            $table->string('hata_mesaji', 500)->nullable();
            $table->timestamp('gonderim_tarihi');
            $table->timestamps();

            $table->index('kanal');
            $table->index('durum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurban_bildirimler');
    }
};