<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bagis_otomatik_raporlar', function (Blueprint $table) {
            $table->id();
            $table->string('periyot', 20);
            $table->json('alicilar');
            $table->boolean('aktif')->default(true);
            $table->timestamp('son_gonderim')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bagis_otomatik_raporlar');
    }
};
