<?php

use App\Enums\UyeDurumu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uyeler', function (Blueprint $table) {
            $table->id();
            $table->string('ad_soyad', 255);
            $table->string('telefon', 20)->nullable()->unique();
            $table->string('eposta')->nullable()->unique();
            $table->string('sifre')->nullable();
            $table->boolean('telefon_dogrulandi')->default(false);
            $table->boolean('eposta_dogrulandi')->default(false);
            $table->boolean('sms_abonelik')->default(true);
            $table->boolean('eposta_abonelik')->default(true);
            $table->string('durum', 20)->default(UyeDurumu::Aktif->value);
            $table->timestamp('son_giris')->nullable();
            $table->rememberToken();
            $table->foreignId('kisi_id')->nullable()->constrained('kisiler')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('ad_soyad');
            $table->index('durum');
            $table->index('sms_abonelik');
            $table->index('eposta_abonelik');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uyeler');
    }
};