<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekayit_veli_bilgileri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kayit_id')->constrained('ekayit_kayitlar')->cascadeOnDelete();
            $table->string('ad_soyad');
            $table->string('eposta', 255)->nullable();
            $table->string('telefon_1', 20);
            $table->string('telefon_2', 20)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekayit_veli_bilgileri');
    }
};
