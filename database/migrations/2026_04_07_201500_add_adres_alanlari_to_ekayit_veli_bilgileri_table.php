<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ekayit_veli_bilgileri', function (Blueprint $table) {
            if (! Schema::hasColumn('ekayit_veli_bilgileri', 'adres')) {
                $table->text('adres')->nullable()->after('telefon_2');
            }

            if (! Schema::hasColumn('ekayit_veli_bilgileri', 'ikamet_il')) {
                $table->string('ikamet_il', 100)->nullable()->after('adres');
            }

            if (! Schema::hasColumn('ekayit_veli_bilgileri', 'ikamet_ilce')) {
                $table->string('ikamet_ilce', 100)->nullable()->after('ikamet_il');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ekayit_veli_bilgileri', function (Blueprint $table) {
            $silinecekAlanlar = array_filter([
                Schema::hasColumn('ekayit_veli_bilgileri', 'ikamet_ilce') ? 'ikamet_ilce' : null,
                Schema::hasColumn('ekayit_veli_bilgileri', 'ikamet_il') ? 'ikamet_il' : null,
                Schema::hasColumn('ekayit_veli_bilgileri', 'adres') ? 'adres' : null,
            ]);

            if ($silinecekAlanlar !== []) {
                $table->dropColumn($silinecekAlanlar);
            }
        });
    }
};
