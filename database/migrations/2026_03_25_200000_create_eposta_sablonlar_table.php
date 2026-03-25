<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eposta_sablonlar', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 100)->unique();
            $table->string('ad', 255);
            $table->string('konu', 255);
            $table->string('tip', 30); // otp / bildirim / makbuz / onay / sistem
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eposta_sablonlar');
    }
};
