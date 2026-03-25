<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurumsal_iletisim_lokasyonlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sayfa_id')->constrained('kurumsal_sayfalar')->cascadeOnDelete();
            $table->string('lokasyon_adi', 255);
            $table->text('adres');
            $table->string('eposta', 255)->nullable();
            $table->decimal('konum_lat', 10, 7)->nullable();
            $table->decimal('konum_lng', 10, 7)->nullable();
            $table->string('konum_place_id')->nullable();
            $table->unsignedSmallInteger('sira')->default(0);
            $table->timestamps();

            $table->index('sayfa_id');
            $table->index('sira');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurumsal_iletisim_lokasyonlari');
    }
};
