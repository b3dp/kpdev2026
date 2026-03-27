<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekayit_kimlik_bilgileri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kayit_id')->constrained('ekayit_kayitlar')->cascadeOnDelete();
            $table->string('kayitli_il', 100)->nullable();
            $table->string('kayitli_ilce', 100)->nullable();
            $table->string('kayitli_mahalle_koy', 255)->nullable();
            $table->string('cilt_no', 50)->nullable();
            $table->string('aile_sira_no', 50)->nullable();
            $table->string('sira_no', 50)->nullable();
            $table->string('cuzdanin_verildigi_yer', 255)->nullable();
            $table->string('kimlik_seri_no', 50)->nullable();
            $table->string('kan_grubu', 10)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekayit_kimlik_bilgileri');
    }
};
