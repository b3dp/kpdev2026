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
            $table->string('onay_token', 64)->nullable()->unique()->after('gorsel_mobil_lg');
            $table->timestamp('onay_token_expires_at')->nullable()->after('onay_token');
            $table->timestamp('onay_epostasi_gonderildi_at')->nullable()->after('onay_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('haberler', function (Blueprint $table) {
            $table->dropColumn(['onay_token', 'onay_token_expires_at', 'onay_epostasi_gonderildi_at']);
        });
    }
};
