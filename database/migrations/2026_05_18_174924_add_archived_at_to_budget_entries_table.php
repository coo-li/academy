<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_entries', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('is_deductible_from_allowance');
            $table->unsignedBigInteger('archived_by')->nullable()->after('archived_at');
            
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('budget_entries', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['archived_at', 'archived_by']);
        });
    }
};
