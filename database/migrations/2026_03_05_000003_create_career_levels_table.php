<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_path_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['career_path_id', 'level_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_levels');
    }
};
