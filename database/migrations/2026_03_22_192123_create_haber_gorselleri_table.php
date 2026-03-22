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
        Schema::create('haber_gorselleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('haber_id')->constrained('haberler')->cascadeOnDelete();
            $table->smallInteger('sira')->default(0);
            $table->string('orijinal_yol')->nullable();
            $table->string('lg_yol')->nullable();
            $table->string('og_yol')->nullable();
            $table->string('sm_yol')->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('haber_id');
            $table->index(['haber_id', 'sira']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('haber_gorselleri');
    }
};
