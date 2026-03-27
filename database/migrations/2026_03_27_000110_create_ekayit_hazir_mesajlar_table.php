<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekayit_hazir_mesajlar', function (Blueprint $table) {
            $table->id();
            $table->string('baslik');
            $table->text('metin');
            $table->string('tip', 20);
            $table->boolean('aktif')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekayit_hazir_mesajlar');
    }
};
