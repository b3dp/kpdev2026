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
        Schema::table('sms_kisiler', function (Blueprint $table): void {
            $table->dropUnique('sms_kisiler_telefon_unique');
            $table->index('telefon', 'sms_kisiler_telefon_index');
        });
    }

    public function down(): void
    {
        Schema::table('sms_kisiler', function (Blueprint $table): void {
            $table->dropIndex('sms_kisiler_telefon_index');
            $table->unique('telefon', 'sms_kisiler_telefon_unique');
        });
    }
};
