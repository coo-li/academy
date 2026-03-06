<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('personio_level_raw')->nullable()->after('personio_department');
            $table->string('personio_path_raw')->nullable()->after('personio_level_raw');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['personio_level_raw', 'personio_path_raw']);
        });
    }
};
