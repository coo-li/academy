<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'budget_tracker_id')) {
                $table->string('budget_tracker_id')->nullable()->after('remember_token');
                $table->index('budget_tracker_id');
            }
            
            if (!Schema::hasColumn('users', 'employee_category')) {
                $table->string('employee_category')->nullable()->after('budget_tracker_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'budget_tracker_id')) {
                $table->dropIndex(['budget_tracker_id']);
                $table->dropColumn('budget_tracker_id');
            }
            
            if (Schema::hasColumn('users', 'employee_category')) {
                $table->dropColumn('employee_category');
            }
        });
    }
};
