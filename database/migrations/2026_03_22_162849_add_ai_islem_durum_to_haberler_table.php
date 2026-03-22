<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haberler', function (Blueprint $table) {
            $table->unsignedTinyInteger('ai_islem_yuzde')->default(0)->after('ai_islendi');
            $table->string('ai_islem_adim', 120)->nullable()->after('ai_islem_yuzde');
        });
    }

    public function down(): void
    {
        Schema::table('haberler', function (Blueprint $table) {
            $table->dropColumn(['ai_islem_yuzde', 'ai_islem_adim']);
        });
    }
};
