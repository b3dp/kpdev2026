<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurban_kayitlar', function (Blueprint $table) {
            $table->id();
            $table->string('kurban_no', 20)->unique();
            $table->foreignId('bagis_id')->constrained('bagislar')->cascadeOnDelete();
            $table->foreignId('bagis_kalem_id')->unique()->constrained('bagis_kalemleri')->cascadeOnDelete();
            $table->string('bagis_turu_adi', 255);
            $table->string('bagis_ozelligi', 30);
            $table->string('durum', 20)->default('bekliyor');
            $table->timestamp('kesim_tarihi')->nullable();
            $table->string('kesim_yeri', 500)->nullable();
            $table->string('kesim_gorevlisi', 255)->nullable();
            $table->tinyInteger('hisse_sayisi')->nullable();
            $table->string('bildirim_durumu', 20)->default('gonderilmedi');
            $table->text('not')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('durum');
            $table->index('bildirim_durumu');
            $table->index('bagis_ozelligi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurban_kayitlar');
    }
};