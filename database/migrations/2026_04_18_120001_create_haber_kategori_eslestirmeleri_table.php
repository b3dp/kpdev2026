<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haber_kategori_eslestirmeleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('haber_id')->constrained('haberler')->cascadeOnDelete();
            $table->foreignId('haber_kategorisi_id')->constrained('haber_kategorileri')->cascadeOnDelete();
            $table->unsignedTinyInteger('skor')->default(0);
            $table->boolean('ana_kategori_mi')->default(false);
            $table->string('kaynak', 30)->default('manuel');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['haber_id', 'haber_kategorisi_id'], 'haber_kategori_unique');
            $table->index(['haber_kategorisi_id', 'ana_kategori_mi'], 'haber_kategori_ana_idx');
        });

        DB::table('haberler')
            ->whereNotNull('kategori_id')
            ->orderBy('id')
            ->chunkById(200, function ($haberler): void {
                $simdi = now();
                $kayitlar = [];

                foreach ($haberler as $haber) {
                    $kayitlar[] = [
                        'haber_id' => $haber->id,
                        'haber_kategorisi_id' => $haber->kategori_id,
                        'skor' => 100,
                        'ana_kategori_mi' => true,
                        'kaynak' => 'legacy',
                        'created_at' => $simdi,
                        'updated_at' => $simdi,
                        'deleted_at' => null,
                    ];
                }

                if ($kayitlar !== []) {
                    DB::table('haber_kategori_eslestirmeleri')->insertOrIgnore($kayitlar);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('haber_kategori_eslestirmeleri');
    }
};