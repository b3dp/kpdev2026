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
        Schema::create('sms_kredileri', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('kalan_kredi')->default(100000)->comment('Kalan SMS Kredi');
            $table->longText('notlar')->nullable()->comment('Kredi işlemleri notu');
            $table->timestamps();
        });

        // İlk kayıt: 100.000 kredi
        \Illuminate\Support\Facades\DB::table('sms_kredileri')->insert([
            'kalan_kredi' => 100000,
            'notlar' => 'Başlangıç Kredi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_kredileri');
    }
};
