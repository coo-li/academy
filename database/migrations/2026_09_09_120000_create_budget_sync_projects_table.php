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
        Schema::create('budget_sync_projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_id')->unique();      // ID aus dem Budget-Tracker
            $table->string('project_name');               // Name zur Anzeige
            $table->string('customer_name')->nullable();  // z.B. "trafficdesign"
            $table->boolean('is_active')->default(true);  // Kann deaktiviert werden
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_sync_projects');
    }
};
