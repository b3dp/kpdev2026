<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haber_kisiler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('haber_id')->constrained('haberler')->cascadeOnDelete();
            $table->foreignId('kisi_id')->constrained('kisiler')->cascadeOnDelete();
            $table->string('rol', 100)->nullable();
            $table->string('onay_durumu', 20)->default('beklemede');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['haber_id', 'kisi_id']);
            $table->index('onay_durumu');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haber_kisiler');
    }
};
