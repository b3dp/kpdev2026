<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurumlar', function (Blueprint $table) {
            $table->id();
            $table->string('ad', 500);
            $table->string('slug', 500)->unique();
            $table->string('tip', 50);
            $table->string('telefon', 20)->nullable();
            $table->string('eposta')->nullable();
            $table->text('adres')->nullable();
            $table->string('il', 100)->nullable();
            $table->string('ilce', 100)->nullable();
            $table->string('web_sitesi')->nullable();
            $table->text('aciklama')->nullable();
            $table->boolean('aktif')->default(false);
            $table->foreignId('kurumsal_sayfa_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('ad');
            $table->index('il');
            $table->index('tip');
            $table->index('aktif');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurumlar');
    }
};