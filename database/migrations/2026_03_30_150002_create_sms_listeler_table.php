<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_listeler', function (Blueprint $table): void {
            $table->id();
            $table->string('ad', 255);
            $table->foreignId('sahip_yonetici_id')->nullable()->constrained('yoneticiler')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_listeler');
    }
};
