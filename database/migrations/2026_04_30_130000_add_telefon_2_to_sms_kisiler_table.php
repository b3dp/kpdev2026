<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_kisiler', function (Blueprint $table): void {
            $table->string('telefon_2', 20)->nullable()->after('telefon');
            $table->index('telefon_2');
        });
    }

    public function down(): void
    {
        Schema::table('sms_kisiler', function (Blueprint $table): void {
            $table->dropIndex(['telefon_2']);
            $table->dropColumn('telefon_2');
        });
    }
};
