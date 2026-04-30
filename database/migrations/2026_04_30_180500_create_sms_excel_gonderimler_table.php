<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_excel_gonderimler', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('yonetici_id')->nullable()->constrained('yoneticiler')->nullOnDelete();
            $table->string('dosya')->nullable();
            $table->text('mesaj');
            $table->string('durum', 30)->default('bekliyor')->index();
            $table->unsignedInteger('toplam_satir')->default(0);
            $table->unsignedInteger('gecerli_satir')->default(0);
            $table->unsignedInteger('mukerrer')->default(0);
            $table->unsignedInteger('hatali_format')->default(0);
            $table->unsignedInteger('bos')->default(0);
            $table->unsignedInteger('alici_sayisi')->default(0);
            $table->unsignedInteger('basarili')->default(0);
            $table->unsignedInteger('basarisiz')->default(0);
            $table->unsignedInteger('bekleyen')->default(0);
            $table->string('hermes_transaction_id')->nullable();
            $table->string('hermes_async_req_id')->nullable();
            $table->text('hata_mesaji')->nullable();
            $table->timestamp('basladi_at')->nullable();
            $table->timestamp('tamamlandi_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('yonetici_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_excel_gonderimler');
    }
};
