<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haber_kurumlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('haber_id')->constrained('haberler')->cascadeOnDelete();
            $table->foreignId('kurum_id')->constrained('kurumlar')->cascadeOnDelete();
            $table->string('onay_durumu', 20)->default('beklemede');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['haber_id', 'kurum_id']);
            $table->index('onay_durumu');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haber_kurumlar');
    }
};
