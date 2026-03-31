<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_kisiler', function (Blueprint $table): void {
            $table->id();
            $table->string('telefon', 20)->unique();
            $table->string('ad_soyad', 255)->nullable();
            $table->text('notlar')->nullable();
            $table->foreignId('created_by')->constrained('yoneticiler');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_kisiler');
    }
};
