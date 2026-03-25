<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_level_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_level_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'career_level_id']);
        });

        DB::table('users')
            ->whereNotNull('career_level_id')
            ->orderBy('id')
            ->each(function ($user) {
                DB::table('career_level_user')->insert([
                    'user_id' => $user->id,
                    'career_level_id' => $user->career_level_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_level_user');
    }
};
