<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personio_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['success', 'error', 'partial'])->default('success');
            $table->integer('employees_fetched')->default(0);
            $table->integer('users_created')->default(0);
            $table->integer('users_updated')->default(0);
            $table->integer('users_skipped')->default(0);
            $table->text('error_message')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personio_sync_logs');
    }
};
