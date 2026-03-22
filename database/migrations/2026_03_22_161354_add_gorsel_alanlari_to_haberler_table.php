<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haberler', function (Blueprint $table) {
            $table->string('gorsel_orijinal')->nullable()->after('ai_onay');
            $table->string('gorsel_lg')->nullable()->after('gorsel_orijinal');
            $table->string('gorsel_og')->nullable()->after('gorsel_lg');
            $table->string('gorsel_sm')->nullable()->after('gorsel_og');
            $table->string('gorsel_mobil_lg')->nullable()->after('gorsel_sm');
        });
    }

    public function down(): void
    {
        Schema::table('haberler', function (Blueprint $table) {
            $table->dropColumn([
                'gorsel_orijinal',
                'gorsel_lg',
                'gorsel_og',
                'gorsel_sm',
                'gorsel_mobil_lg',
            ]);
        });
    }
};
