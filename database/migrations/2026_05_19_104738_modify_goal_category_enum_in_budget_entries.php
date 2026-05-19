<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE budget_entries MODIFY goal_category ENUM('A', 'B', 'C', 'none') NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE budget_entries SET goal_category = NULL WHERE goal_category = 'none'");
        DB::statement("ALTER TABLE budget_entries MODIFY goal_category ENUM('A', 'B', 'C') NULL");
    }
};
