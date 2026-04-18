<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haber_ai_revizyonlari', function (Blueprint $table) {
            $table->string('orijinal_seo_baslik', 255)->nullable()->after('duzeltilmis_ozet');
            $table->string('duzeltilmis_seo_baslik', 255)->nullable()->after('orijinal_seo_baslik');
        });
    }

    public function down(): void
    {
        Schema::table('haber_ai_revizyonlari', function (Blueprint $table) {
            $table->dropColumn(['orijinal_seo_baslik', 'duzeltilmis_seo_baslik']);
        });
    }
};