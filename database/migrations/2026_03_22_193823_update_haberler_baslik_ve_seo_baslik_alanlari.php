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
        Schema::table('haberler', function (Blueprint $table) {
            $table->string('baslik', 100)->change();
            $table->string('seo_baslik', 60)->nullable()->after('baslik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('haberler', function (Blueprint $table) {
            $table->dropColumn('seo_baslik');
            $table->string('baslik', 60)->change();
        });
    }
};
