<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dateTime('attendance_confirmed_at')->nullable()->after('completed_at');
            $table->foreignId('attendance_confirmed_by')->nullable()->after('attendance_confirmed_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['attendance_confirmed_by']);
            $table->dropColumn(['attendance_confirmed_at', 'attendance_confirmed_by']);
        });
    }
};
