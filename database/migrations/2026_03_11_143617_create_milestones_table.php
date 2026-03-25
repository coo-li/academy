<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('passiv');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['career_level_id', 'team_id']);
        });

        Schema::create('disabled_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disabled_at');
            $table->timestamps();

            $table->unique(['user_id', 'milestone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disabled_milestones');
        Schema::dropIfExists('milestones');
    }
};
