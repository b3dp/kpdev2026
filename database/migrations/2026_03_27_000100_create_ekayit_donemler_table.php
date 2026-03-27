<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekayit_donemler', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('ogretim_yili', 20);
            $table->datetime('baslangic');
            $table->datetime('bitis');
            $table->boolean('aktif')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekayit_donemler');
    }
};
