<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_liste_kisiler', function (Blueprint $table): void {
            $table->foreignId('liste_id')->constrained('sms_listeler')->cascadeOnDelete();
            $table->foreignId('kisi_id')->constrained('sms_kisiler')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['liste_id', 'kisi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_liste_kisiler');
    }
};
