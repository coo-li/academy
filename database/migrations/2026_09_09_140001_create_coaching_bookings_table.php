<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coaching_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');           // Für wen
            $table->foreignId('booked_by_user_id')->constrained('users')->onDelete('cascade'); // Wer hat gebucht
            
            $table->date('booking_date');
            $table->enum('coaching_type', ['full', 'quick_help']);                      // Voll=1000€, Quick=750€
            $table->decimal('cost', 10, 2);                                             // Kosten (automatisch berechnet)
            $table->string('coach_name')->nullable();                                   // Name des Coaches
            $table->text('notes')->nullable();                                          // Anmerkungen
            
            // C-Level Genehmigung bei Budgetüberschreitung
            $table->boolean('clevel_approved')->default(false);
            $table->foreignId('clevel_approved_by')->nullable()->constrained('users');
            $table->timestamp('clevel_approved_at')->nullable();
            
            $table->timestamps();
            
            // Indizes
            $table->index(['user_id', 'booking_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coaching_bookings');
    }
};
