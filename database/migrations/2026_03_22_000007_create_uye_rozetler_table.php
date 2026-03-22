<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uye_rozetler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uye_id')->constrained('uyeler')->cascadeOnDelete();
            $table->string('tip', 50);
            $table->timestamp('kazanilma_tarihi')->useCurrent();
            $table->string('kaynak_tip', 100)->nullable();
            $table->unsignedBigInteger('kaynak_id')->nullable();
            $table->timestamps();

            $table->unique(['uye_id', 'tip']);
            $table->index('tip');
            $table->index('created_at');
            $table->index(['kaynak_tip', 'kaynak_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uye_rozetler');
    }
};