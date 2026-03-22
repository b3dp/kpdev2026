<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uyeler', function (Blueprint $table) {
            $table->boolean('aktif')->default(true)->after('durum');
        });
    }

    public function down(): void
    {
        Schema::table('uyeler', function (Blueprint $table) {
            $table->dropColumn('aktif');
        });
    }
};
