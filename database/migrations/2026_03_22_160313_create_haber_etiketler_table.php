<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haber_etiketler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('haber_id')->constrained('haberler')->cascadeOnDelete();
            $table->foreignId('etiket_id')->constrained('etiketler')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['haber_id', 'etiket_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haber_etiketler');
    }
};
