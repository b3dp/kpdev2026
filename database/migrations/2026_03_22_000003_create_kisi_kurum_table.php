<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kisi_kurum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kisi_id')->constrained('kisiler')->cascadeOnDelete();
            $table->foreignId('kurum_id')->constrained('kurumlar')->cascadeOnDelete();
            $table->string('gorev', 255)->nullable();
            $table->date('baslangic_tarihi')->nullable();
            $table->date('bitis_tarihi')->nullable();
            $table->boolean('aktif')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kisi_id', 'kurum_id']);
            $table->index('aktif');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kisi_kurum');
    }
};