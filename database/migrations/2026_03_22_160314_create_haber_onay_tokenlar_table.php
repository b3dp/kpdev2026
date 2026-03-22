<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haber_onay_tokenlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('haber_id')->constrained('haberler')->cascadeOnDelete();
            $table->string('token', 100)->unique();
            $table->string('tip', 20);
            $table->boolean('kullanildi')->default(false);
            $table->timestamp('gecerlilik_tarihi');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['haber_id', 'kullanildi']);
            $table->index('gecerlilik_tarihi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haber_onay_tokenlar');
    }
};
