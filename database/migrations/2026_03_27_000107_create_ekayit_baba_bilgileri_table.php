<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekayit_baba_bilgileri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kayit_id')->constrained('ekayit_kayitlar')->cascadeOnDelete();
            $table->string('dogum_yeri', 255)->nullable();
            $table->string('nufus_il_ilce', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekayit_baba_bilgileri');
    }
};
