<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uye_bildirimler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uye_id')->constrained('uyeler')->cascadeOnDelete();
            $table->string('tip', 50); // rozet, vb.
            $table->text('mesaj');
            $table->boolean('okundu')->default(false);
            $table->timestamps();

            $table->index(['uye_id', 'okundu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uye_bildirimler');
    }
};
