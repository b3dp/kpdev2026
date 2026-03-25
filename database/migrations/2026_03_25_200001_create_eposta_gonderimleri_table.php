<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eposta_gonderimleri', function (Blueprint $table) {
            $table->id();
            $table->string('sablon_kodu', 100);
            $table->string('alici_eposta', 255);
            $table->string('alici_ad', 255)->nullable();
            $table->string('konu', 255);
            $table->string('durum', 30)->default('beklemede'); // beklemede / gonderildi / basarisiz
            $table->string('zeptomail_message_id', 255)->nullable();
            $table->string('hata_mesaji', 500)->nullable();
            $table->string('ilgili_tip', 100)->nullable();
            $table->unsignedBigInteger('ilgili_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('sablon_kodu');
            $table->index('alici_eposta');
            $table->index('durum');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eposta_gonderimleri');
    }
};
