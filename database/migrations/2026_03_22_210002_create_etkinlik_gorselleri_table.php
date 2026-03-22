<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etkinlik_gorselleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etkinlik_id')->constrained('etkinlikler')->cascadeOnDelete();
            $table->smallInteger('sira')->default(0);
            $table->string('orijinal_yol')->nullable();
            $table->string('lg_yol')->nullable();
            $table->string('og_yol')->nullable();
            $table->string('sm_yol')->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('etkinlik_id');
            $table->index(['etkinlik_id', 'sira']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etkinlik_gorselleri');
    }
};
