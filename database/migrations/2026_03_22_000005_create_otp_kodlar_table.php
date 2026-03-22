<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_kodlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uye_id')->nullable()->constrained('uyeler')->nullOnDelete();
            $table->string('telefon', 20);
            $table->string('eposta')->nullable();
            $table->string('kod', 6);
            $table->string('tip', 50);
            $table->boolean('kullanildi')->default(false);
            $table->timestamp('gecerlilik_tarihi');
            $table->timestamp('created_at')->useCurrent();

            $table->index('telefon');
            $table->index('eposta');
            $table->index('tip');
            $table->index('kullanildi');
            $table->index('gecerlilik_tarihi');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_kodlar');
    }
};