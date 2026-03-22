<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uye_id')->constrained('uyeler')->cascadeOnDelete();
            $table->string('device_token', 64)->unique();
            $table->string('device_adi', 255)->nullable();
            $table->string('ip_adresi', 45)->nullable();
            $table->timestamp('son_kullanim')->useCurrent();
            $table->timestamp('gecerlilik_tarihi')->useCurrent();
            $table->timestamps();

            $table->index('son_kullanim');
            $table->index('gecerlilik_tarihi');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};