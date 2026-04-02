<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bagislar', function (Blueprint $table) {
            $table->unsignedBigInteger('kisi_id')->nullable()->after('uye_id');
            $table->foreign('kisi_id')->references('id')->on('kisiler')->nullOnDelete();
            $table->index('kisi_id');
        });
    }

    public function down(): void
    {
        Schema::table('bagislar', function (Blueprint $table) {
            $table->dropForeign(['kisi_id']);
            $table->dropIndex(['kisi_id']);
            $table->dropColumn('kisi_id');
        });
    }
};
