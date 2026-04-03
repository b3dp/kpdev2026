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
    Schema::table('bagis_turleri', function (Blueprint $table) {
        $table->unsignedSmallInteger('sira')->default(0)->after('aktif');
    });
}

public function down(): void
{
    Schema::table('bagis_turleri', function (Blueprint $table) {
        $table->dropColumn('sira');
    });
}
};
