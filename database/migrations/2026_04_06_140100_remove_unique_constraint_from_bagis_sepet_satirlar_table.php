<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bagis_sepet_satirlar', function (Blueprint $table) {
            $table->index('sepet_id', 'bagis_sepet_satirlar_sepet_id_index');
            $table->dropUnique('bagis_sepet_satirlar_sepet_id_bagis_turu_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bagis_sepet_satirlar', function (Blueprint $table) {
            $table->unique(['sepet_id', 'bagis_turu_id']);
            $table->dropIndex('bagis_sepet_satirlar_sepet_id_index');
        });
    }
};
