<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haber_kategorileri', function (Blueprint $table) {
            $table->id();
            $table->string('ad', 150);
            $table->string('slug', 180)->unique();
            $table->string('renk', 20)->nullable();
            $table->unsignedInteger('sira')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('aktif');
            $table->index('sira');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haber_kategorileri');
    }
};
