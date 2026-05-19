<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_budget_rules', function (Blueprint $table) {
            $table->string('rule_type', 20)->default('base')->after('description');
            $table->decimal('max_cash_budget', 10, 2)->nullable()->after('max_money_budget');
            
            $table->index('rule_type');
        });

        // Make max_money_budget nullable (for overlay rules)
        Schema::table('employee_budget_rules', function (Blueprint $table) {
            $table->decimal('max_money_budget', 10, 2)->nullable()->change();
        });

        // Set all existing rules to 'base' type
        DB::table('employee_budget_rules')->update(['rule_type' => 'base']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set default value for max_money_budget before making it required again
        DB::table('employee_budget_rules')
            ->whereNull('max_money_budget')
            ->update(['max_money_budget' => 0]);

        Schema::table('employee_budget_rules', function (Blueprint $table) {
            $table->dropIndex(['rule_type']);
            $table->dropColumn(['rule_type', 'max_cash_budget']);
        });

        Schema::table('employee_budget_rules', function (Blueprint $table) {
            $table->decimal('max_money_budget', 10, 2)->nullable(false)->change();
        });
    }
};
