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
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['career_level_id']);
            $table->unsignedBigInteger('career_level_id')->nullable()->change();
            $table->foreign('career_level_id')
                ->references('id')->on('career_levels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['career_level_id']);
            $table->unsignedBigInteger('career_level_id')->nullable(false)->change();
            $table->foreign('career_level_id')
                ->references('id')->on('career_levels')
                ->cascadeOnDelete();
        });
    }
};
