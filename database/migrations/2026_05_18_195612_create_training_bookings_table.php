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
        Schema::create('training_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('booked_by_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->decimal('net_cost', 10, 2);
            $table->boolean('requires_gross_billing')->default(false);
            $table->boolean('during_work_hours')->default(false);
            $table->decimal('hours', 8, 2)->nullable();
            $table->string('asana_task_gid')->nullable();
            $table->boolean('budget_entry_created')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'budget_entry_created']);
            $table->index('booked_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_bookings');
    }
};
