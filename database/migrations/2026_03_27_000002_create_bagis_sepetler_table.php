<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bagis_sepetler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uye_id')->nullable()->constrained('uyeler')->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->string('durum', 30)->default('aktif');
            $table->decimal('toplam_tutar', 10, 2)->default(0);
            $table->timestamps();
            $table->index(['uye_id', 'durum']);
            $table->index(['session_id', 'durum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bagis_sepetler');
    }
};
