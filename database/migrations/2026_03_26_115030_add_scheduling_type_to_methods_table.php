<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('methods', function (Blueprint $table) {
            $table->string('scheduling_type')->default('scheduled')->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('methods', function (Blueprint $table) {
            $table->dropColumn('scheduling_type');
        });
    }
};
