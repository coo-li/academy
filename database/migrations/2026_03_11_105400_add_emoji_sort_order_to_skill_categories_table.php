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
        Schema::table('skill_categories', function (Blueprint $table) {
            $table->string('emoji', 8)->nullable()->after('name');
            $table->integer('sort_order')->default(0)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('skill_categories', function (Blueprint $table) {
            $table->dropColumn(['emoji', 'sort_order']);
        });
    }
};
