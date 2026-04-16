<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mezun_profiller', function (Blueprint $table) {
            $table->text('acik_adres')->nullable()->after('ikamet_ilce');
            $table->text('aciklama')->nullable()->after('acik_adres');
        });
    }

    public function down(): void
    {
        Schema::table('mezun_profiller', function (Blueprint $table) {
            $table->dropColumn(['acik_adres', 'aciklama']);
        });
    }
};