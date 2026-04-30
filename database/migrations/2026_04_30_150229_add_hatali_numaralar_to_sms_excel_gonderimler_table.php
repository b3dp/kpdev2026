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
        if (! Schema::hasTable('sms_excel_gonderimler')) {
            return;
        }

        Schema::table('sms_excel_gonderimler', function (Blueprint $table): void {
            $table->json('hatali_numaralar')->nullable()->after('hatali_format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('sms_excel_gonderimler')) {
            return;
        }

        Schema::table('sms_excel_gonderimler', function (Blueprint $table): void {
            $table->dropColumn('hatali_numaralar');
        });
    }
};
