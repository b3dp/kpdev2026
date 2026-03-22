<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kisiler', function (Blueprint $table) {
            $table->id();
            $table->string('ad', 100);
            $table->string('soyad', 100);
            $table->string('cinsiyet', 20)->default('belirtilmemis');
            $table->date('dogum_tarihi')->nullable();
            $table->string('tc_kimlik', 11)->nullable()->unique();
            $table->string('telefon', 20)->nullable();
            $table->string('eposta')->nullable();
            $table->text('adres')->nullable();
            $table->string('il', 100)->nullable();
            $table->string('ilce', 100)->nullable();
            $table->string('meslek', 255)->nullable();
            $table->text('notlar')->nullable();
            $table->boolean('ai_onaylandi')->default(false);
            $table->decimal('ai_skoru', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('ad');
            $table->index('soyad');
            $table->index('telefon');
            $table->index('eposta');
            $table->index('cinsiyet');
            $table->index('ai_onaylandi');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kisiler');
    }
};