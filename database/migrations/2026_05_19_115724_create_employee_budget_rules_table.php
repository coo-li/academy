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
        Schema::create('employee_budget_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('max_money_budget', 10, 2);
            $table->decimal('time_money_ratio', 5, 2)->nullable();
            $table->decimal('hourly_rate_override', 8, 2)->nullable();
            $table->decimal('working_hours_min', 5, 2)->nullable();
            $table->decimal('working_hours_max', 5, 2)->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_default')->default(false);
            $table->json('applies_to_departments')->nullable();
            $table->json('applies_to_positions')->nullable();
            $table->json('applies_to_levels')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('priority');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_budget_rules');
    }
};
