<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_budget_settings', function (Blueprint $table) {
            $table->id();
            $table->year('year')->unique();
            $table->decimal('revenue_target', 15, 2)->default(0)->comment('Umsatzziel in Euro');
            $table->decimal('service_dev_percentage', 5, 2)->default(0)->comment('Prozentsatz vom Umsatz für Service Development');
            $table->timestamps();
        });

        // Team-Budget-Allocation: Prozentuale Verteilung pro Team
        Schema::create('team_budget_allocations', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->decimal('allocation_percentage', 5, 2)->default(0)->comment('Anteil am Service Dev Budget in %');
            $table->timestamps();

            $table->unique(['year', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_budget_allocations');
        Schema::dropIfExists('company_budget_settings');
    }
};
