<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personio_position_mappings', function (Blueprint $table) {
            $table->boolean('is_auto_matched')->default(false)->after('career_level_id');
        });
    }

    public function down(): void
    {
        Schema::table('personio_position_mappings', function (Blueprint $table) {
            $table->dropColumn('is_auto_matched');
        });
    }
};
