<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('budget_entries')) {
            return;
        }

        Schema::create('budget_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('label');
            $table->enum('type', ['personal_goal', 'internal_training', 'team_goal', 'other_internal']);
            $table->enum('cost_type', ['monetary', 'time']);
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->boolean('is_deductible_from_allowance')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_entries');
    }
};
