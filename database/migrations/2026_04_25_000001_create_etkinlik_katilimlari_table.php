<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('etkinlik_katilimlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etkinlik_id')->constrained('etkinlikler')->cascadeOnDelete();
            $table->foreignId('uye_id')->constrained('uyeler')->cascadeOnDelete();
            $table->string('durum', 32)->default('belirsiz');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['etkinlik_id', 'uye_id']);
            $table->index('durum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etkinlik_katilimlari');
    }
};
