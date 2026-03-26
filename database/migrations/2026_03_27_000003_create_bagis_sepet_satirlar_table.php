<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bagis_sepet_satirlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sepet_id')->constrained('bagis_sepetler')->cascadeOnDelete();
            $table->foreignId('bagis_turu_id')->constrained('bagis_turleri');
            $table->smallInteger('adet')->default(1);
            $table->decimal('birim_fiyat', 10, 2);
            $table->decimal('toplam', 10, 2);
            $table->string('sahip_tipi', 20)->default('kendi');
            $table->boolean('vekalet_onay')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['sepet_id', 'bagis_turu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bagis_sepet_satirlar');
    }
};
