<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sms_excel_gonderimler')) {
            return;
        }

        Schema::table('sms_excel_gonderimler', function (Blueprint $table): void {
            $table->string('rapor_dosya_yolu')->nullable()->after('dosya');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sms_excel_gonderimler')) {
            return;
        }

        Schema::table('sms_excel_gonderimler', function (Blueprint $table): void {
            $table->dropColumn('rapor_dosya_yolu');
        });
    }
};