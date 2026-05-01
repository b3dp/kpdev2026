<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bagislar', function (Blueprint $table) {
            $table->boolean('sms_kvkk_onay')->default(false)->after('odeme_referans');
            $table->index('sms_kvkk_onay');
        });
    }

    public function down(): void
    {
        Schema::table('bagislar', function (Blueprint $table) {
            $table->dropIndex(['sms_kvkk_onay']);
            $table->dropColumn('sms_kvkk_onay');
        });
    }
};
