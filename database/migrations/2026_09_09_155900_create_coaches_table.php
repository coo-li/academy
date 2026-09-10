<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // z.B. "Dieter", "Jakob"
            $table->integer('slots_per_month')->default(4);      // Anzahl Slots pro Monat
            $table->decimal('hourly_rate', 8, 2)->default(250);  // Stundensatz (Standard 250€)
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();             // Optionale Beschreibung
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coaches');
    }
};
