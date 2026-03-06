<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->string('accountable_type')->nullable()->after('skill_category_id');
            $table->foreignId('accountable_user_id')
                ->nullable()
                ->after('accountable_type')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('method_id')
                ->nullable()
                ->after('accountable_user_id')
                ->constrained('methods')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('method_id');
            $table->dropConstrainedForeignId('accountable_user_id');
            $table->dropColumn('accountable_type');
        });
    }
};
