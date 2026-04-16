<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mezun_profiller', function (Blueprint $table) {
            $table->smallInteger('mezuniyet_yili')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mezun_profiller', function (Blueprint $table) {
            $table->smallInteger('mezuniyet_yili')->default(1900)->nullable(false)->change();
        });
    }
};