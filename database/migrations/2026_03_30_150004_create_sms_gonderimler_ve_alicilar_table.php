<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_gonderimler', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('yonetici_id')->constrained('yoneticiler');
            $table->enum('tip', ['hizli', 'toplu', 'bildirim'])->default('hizli');
            $table->text('mesaj');
            $table->json('liste_idler')->nullable();
            $table->integer('alici_sayisi')->default(0);
            $table->integer('basarili')->default(0);
            $table->integer('basarisiz')->default(0);
            $table->integer('bekleyen')->default(0);
            $table->enum('durum', ['beklemede', 'gonderiliyor', 'tamamlandi', 'basarisiz', 'iptal'])->default('beklemede');
            $table->string('hermes_transaction_id')->nullable();
            $table->string('hermes_async_req_id')->nullable();
            $table->timestamp('planli_tarih')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_gonderim_alicilari', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gonderim_id')->constrained('sms_gonderimler')->cascadeOnDelete();
            $table->string('telefon', 20);
            $table->enum('durum', ['beklemede', 'basarili', 'basarisiz'])->default('beklemede');
            $table->string('hermes_packet_id')->nullable();
            $table->string('hata_kodu', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gonderim_alicilari');
        Schema::dropIfExists('sms_gonderimler');
    }
};
