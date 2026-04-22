<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE kurumsal_sayfalar MODIFY meta_description TEXT NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE kurumsal_sayfalar MODIFY meta_description VARCHAR(160) NULL');
    }
};
