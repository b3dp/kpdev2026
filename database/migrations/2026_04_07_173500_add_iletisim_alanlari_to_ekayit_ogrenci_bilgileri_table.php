<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ekayit_ogrenci_bilgileri', function (Blueprint $table) {
            if (! Schema::hasColumn('ekayit_ogrenci_bilgileri', 'telefon')) {
                $table->string('telefon', 20)->nullable()->after('tc_kimlik');
            }

            if (! Schema::hasColumn('ekayit_ogrenci_bilgileri', 'eposta')) {
                $table->string('eposta', 255)->nullable()->after('telefon');
            }

            if (! Schema::hasColumn('ekayit_ogrenci_bilgileri', 'ikamet_ilce')) {
                $table->string('ikamet_ilce', 100)->nullable()->after('ikamet_il');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ekayit_ogrenci_bilgileri', function (Blueprint $table) {
            $silinecekKolonlar = array_values(array_filter([
                Schema::hasColumn('ekayit_ogrenci_bilgileri', 'telefon') ? 'telefon' : null,
                Schema::hasColumn('ekayit_ogrenci_bilgileri', 'eposta') ? 'eposta' : null,
                Schema::hasColumn('ekayit_ogrenci_bilgileri', 'ikamet_ilce') ? 'ikamet_ilce' : null,
            ]));

            if ($silinecekKolonlar !== []) {
                $table->dropColumn($silinecekKolonlar);
            }
        });
    }
};
