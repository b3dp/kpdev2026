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
        Schema::table('eposta_gonderimleri', function (Blueprint $table) {
            $table->longText('html_icerik')->nullable()->after('konu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eposta_gonderimleri', function (Blueprint $table) {
            $table->dropColumn('html_icerik');
        });
    }
};
