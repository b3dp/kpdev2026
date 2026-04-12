<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haber_kategorileri', function (Blueprint $table) {
            $table->string('seo_baslik', 100)->nullable()->after('slug');
            $table->string('meta_description', 200)->nullable()->after('seo_baslik');
            $table->longText('aciklama')->nullable()->after('meta_description');
            $table->string('gorsel')->nullable()->after('aciklama');
            $table->string('ikon', 100)->nullable()->after('gorsel');
        });
    }

    public function down(): void
    {
        Schema::table('haber_kategorileri', function (Blueprint $table) {
            $table->dropColumn(['seo_baslik', 'meta_description', 'aciklama', 'gorsel', 'ikon']);
        });
    }
};
