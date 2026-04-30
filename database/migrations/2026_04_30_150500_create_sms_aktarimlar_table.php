<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_aktarimlar', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('yonetici_id')->nullable()->constrained('yoneticiler')->nullOnDelete();
            $table->foreignId('liste_id')->nullable()->constrained('sms_listeler')->nullOnDelete();
            $table->string('dosya')->nullable();
            $table->string('durum', 30)->default('bekliyor')->index();
            $table->unsignedInteger('toplam')->default(0);
            $table->unsignedInteger('eklenen')->default(0);
            $table->unsignedInteger('mukerrer_db')->default(0);
            $table->unsignedInteger('mukerrer_excel')->default(0);
            $table->unsignedInteger('hatali_format')->default(0);
            $table->unsignedInteger('bos')->default(0);
            $table->text('hata_mesaji')->nullable();
            $table->timestamp('basladi_at')->nullable();
            $table->timestamp('tamamlandi_at')->nullable();
            $table->timestamps();

            $table->index('yonetici_id');
            $table->index('liste_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_aktarimlar');
    }
};
