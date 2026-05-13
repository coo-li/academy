<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_session_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('frequency'); // daily, weekly, monthly, custom
            $table->unsignedTinyInteger('frequency_interval')->default(1);
            $table->json('days_of_week')->nullable(); // [1,2,3,4,5] = Mo-Fr
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('max_participants')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_session_series');
    }
};
