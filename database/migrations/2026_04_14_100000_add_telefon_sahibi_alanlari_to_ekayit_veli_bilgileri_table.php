<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ekayit_veli_bilgileri', function (Blueprint $table) {
            if (! Schema::hasColumn('ekayit_veli_bilgileri', 'telefon_1_sahibi')) {
                $table->string('telefon_1_sahibi', 20)->nullable()->after('eposta');
            }

            if (! Schema::hasColumn('ekayit_veli_bilgileri', 'telefon_2_sahibi')) {
                $table->string('telefon_2_sahibi', 20)->nullable()->after('telefon_1');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ekayit_veli_bilgileri', function (Blueprint $table) {
            $silinecekAlanlar = array_filter([
                Schema::hasColumn('ekayit_veli_bilgileri', 'telefon_1_sahibi') ? 'telefon_1_sahibi' : null,
                Schema::hasColumn('ekayit_veli_bilgileri', 'telefon_2_sahibi') ? 'telefon_2_sahibi' : null,
            ]);

            if ($silinecekAlanlar !== []) {
                $table->dropColumn($silinecekAlanlar);
            }
        });
    }
};
