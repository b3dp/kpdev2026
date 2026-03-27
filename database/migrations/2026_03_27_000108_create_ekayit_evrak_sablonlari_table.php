<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekayit_evrak_sablonlari', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('dosya_adi', 255);
            $table->string('sablon_yol');
            $table->json('degiskenler')->nullable();
            $table->boolean('sadece_onayliya')->default(true);
            $table->tinyInteger('sira')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekayit_evrak_sablonlari');
    }
};
