<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('global_budgets')) {
            return;
        }

        Schema::create('global_budgets', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->enum('category', ['Service', 'Weiterbildung', 'Sonstiges']);
            $table->enum('budget_type', ['Echtkosten', 'Zeit']);
            $table->decimal('amount_planned', 12, 2);
            $table->timestamps();

            $table->unique(['year', 'team_id', 'category', 'budget_type'], 'global_budgets_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_budgets');
    }
};
