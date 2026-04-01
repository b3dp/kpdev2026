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
        Schema::table('haberler', function (Blueprint $table) {
            $table->timestamp('onay_sms_gonderildi_at')->nullable()->after('onay_epostasi_gonderildi_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('haberler', function (Blueprint $table) {
            $table->dropColumn('onay_sms_gonderildi_at');
        });
    }
};
