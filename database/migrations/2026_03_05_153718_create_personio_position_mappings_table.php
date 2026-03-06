<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personio_position_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('personio_position')->unique();
            $table->foreignId('career_path_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('career_level_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personio_position_mappings');
    }
};
