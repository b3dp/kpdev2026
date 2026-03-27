<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekayit_siniflar', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('ogretim_yili', 20);
            $table->foreignId('kurum_id')->constrained('kurumlar');
            $table->foreignId('donem_id')->constrained('ekayit_donemler');
            $table->text('kurallar')->nullable();
            $table->text('aciklama')->nullable();
            $table->text('notlar')->nullable();
            $table->string('gorsel_kare')->nullable();
            $table->string('gorsel_dikey')->nullable();
            $table->string('gorsel_yatay')->nullable();
            $table->string('gorsel_orijinal')->nullable();
            $table->string('renk', 30)->default('blue');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekayit_siniflar');
    }
};
