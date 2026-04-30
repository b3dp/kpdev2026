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
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sms_gonderimler MODIFY COLUMN tip ENUM('hizli','toplu','bildirim_ekayit','bildirim_bagis','bildirim_uyelik','bildirim_etkinlik','bildirim_veli','excel_sms') NOT NULL DEFAULT 'hizli'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sms_gonderimler MODIFY COLUMN tip ENUM('hizli','toplu','bildirim_ekayit','bildirim_bagis','bildirim_uyelik','bildirim_etkinlik','bildirim_veli') NOT NULL DEFAULT 'hizli'");
        }
    }
};
