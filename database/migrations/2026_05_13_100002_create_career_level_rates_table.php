<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('career_level_rates')) {
            return;
        }

        Schema::create('career_level_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_level_id')->constrained('career_levels')->onDelete('cascade');
            $table->decimal('hourly_rate', 10, 2);
            $table->timestamps();

            $table->unique('career_level_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_level_rates');
    }
};
