<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personio_position_mappings', function (Blueprint $table) {
            $table->string('personio_level_raw')->nullable()->after('personio_position');
            $table->string('personio_path_raw')->nullable()->after('personio_level_raw');

            $table->dropUnique(['personio_position']);
            $table->unique(['personio_position', 'personio_level_raw', 'personio_path_raw'], 'ppm_position_level_path_unique');
        });
    }

    public function down(): void
    {
        Schema::table('personio_position_mappings', function (Blueprint $table) {
            $table->dropUnique('ppm_position_level_path_unique');
            $table->unique('personio_position');
            $table->dropColumn(['personio_level_raw', 'personio_path_raw']);
        });
    }
};
