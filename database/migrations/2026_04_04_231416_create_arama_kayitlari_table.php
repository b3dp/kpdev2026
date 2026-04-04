<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('arama_kayitlari', function (Blueprint $table) {
            $table->id();
            $table->string('aranan_ifade')->unique();
            $table->unsignedInteger('arama_sayisi')->default(0);
            $table->timestamp('son_aranma_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('son_aranma_at');
            $table->index(['arama_sayisi', 'son_aranma_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arama_kayitlari');
    }
};
