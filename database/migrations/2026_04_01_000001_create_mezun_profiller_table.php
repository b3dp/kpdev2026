<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mezun_profiller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uye_id')
                ->unique()
                ->constrained('uyeler')
                ->onDelete('cascade');
            $table->foreignId('kurum_id')
                ->nullable()
                ->constrained('kurumlar')
                ->nullOnDelete();
            $table->string('kurum_manuel', 255)->nullable();
            $table->smallInteger('mezuniyet_yili');
            $table->foreignId('sinif_id')
                ->nullable()
                ->references('id')
                ->on('ekayit_siniflar')
                ->nullOnDelete();
            $table->boolean('hafiz')->default(false);
            $table->string('meslek', 255)->nullable();
            $table->string('gorev_il', 100)->nullable();
            $table->string('gorev_ilce', 100)->nullable();
            $table->string('ikamet_il', 100)->nullable();
            $table->string('ikamet_ilce', 100)->nullable();
            $table->string('linkedin', 255)->nullable();
            $table->string('instagram', 255)->nullable();
            $table->string('twitter', 255)->nullable();
            $table->string('durum')->default('beklemede');
            $table->foreignId('onaylayan_id')
                ->nullable()
                ->references('id')
                ->on('yoneticiler')
                ->nullOnDelete();
            $table->timestamp('onay_tarihi')->nullable();
            $table->text('red_notu')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['durum']);
            $table->index(['ikamet_il', 'ikamet_ilce']);
            $table->index(['mezuniyet_yili']);
            $table->index(['kurum_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mezun_profiller');
    }
};
