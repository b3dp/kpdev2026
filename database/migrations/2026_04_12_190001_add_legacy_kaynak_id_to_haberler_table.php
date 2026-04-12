<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haberler', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_kaynak_id')->nullable()->after('canonical_url');
            $table->index('legacy_kaynak_id');
        });
    }

    public function down(): void
    {
        Schema::table('haberler', function (Blueprint $table) {
            $table->dropIndex(['legacy_kaynak_id']);
            $table->dropColumn('legacy_kaynak_id');
        });
    }
};