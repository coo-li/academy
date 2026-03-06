<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('personio_department')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('manager_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'team_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('head_of_user_id')->constrained()->nullOnDelete();
        });

        $departments = DB::table('users')
            ->whereNotNull('personio_department')
            ->where('personio_department', '!=', '')
            ->distinct()
            ->pluck('personio_department');

        foreach ($departments as $dept) {
            $teamId = DB::table('teams')->insertGetId([
                'name' => $dept,
                'personio_department' => $dept,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')
                ->where('personio_department', $dept)
                ->update(['team_id' => $teamId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });

        Schema::dropIfExists('manager_team');
        Schema::dropIfExists('teams');
    }
};
