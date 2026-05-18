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
        Schema::table('budget_entries', function (Blueprint $table) {
            $table->enum('budget_type', ['used', 'available'])->default('used')->after('type');
            $table->string('category', 100)->nullable()->after('label');
            $table->string('budget_name', 255)->nullable()->after('category');
            $table->string('project_id', 50)->nullable()->after('user_id');
            $table->tinyInteger('month')->nullable()->after('date');
            $table->year('year')->nullable()->after('month');
            
            $table->index(['user_id', 'year', 'month', 'budget_type']);
            $table->index(['project_id', 'budget_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_entries', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'year', 'month', 'budget_type']);
            $table->dropIndex(['project_id', 'budget_type']);
            
            $table->dropColumn(['budget_type', 'category', 'budget_name', 'project_id', 'month', 'year']);
        });
    }
};
