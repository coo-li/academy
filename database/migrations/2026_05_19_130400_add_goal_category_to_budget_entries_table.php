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
        if (!Schema::hasColumn('budget_entries', 'goal_category')) {
            Schema::table('budget_entries', function (Blueprint $table) {
                $table->enum('goal_category', ['A', 'B', 'C'])->nullable()->after('is_deductible_from_allowance');
            });
        }
    }

    public function down(): void
    {
        Schema::table('budget_entries', function (Blueprint $table) {
            $table->dropColumn('goal_category');
        });
    }
};
