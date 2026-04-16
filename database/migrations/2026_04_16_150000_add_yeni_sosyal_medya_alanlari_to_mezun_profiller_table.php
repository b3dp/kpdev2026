<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mezun_profiller', function (Blueprint $table) {
            $table->string('nsosyal', 255)->nullable()->after('aciklama');
            $table->string('facebook', 255)->nullable()->after('nsosyal');
            $table->string('youtube', 255)->nullable()->after('facebook');
        });
    }

    public function down(): void
    {
        Schema::table('mezun_profiller', function (Blueprint $table) {
            $table->dropColumn(['nsosyal', 'facebook', 'youtube']);
        });
    }
};