<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurumsal_sayfa_galerileri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sayfa_id')->constrained('kurumsal_sayfalar')->cascadeOnDelete();
            $table->unsignedSmallInteger('sira')->default(0);
            $table->string('orijinal_yol');
            $table->string('lg_yol');
            $table->string('og_yol');
            $table->string('sm_yol');
            $table->string('alt_text', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('sayfa_id');
            $table->index('sira');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurumsal_sayfa_galerileri');
    }
};
