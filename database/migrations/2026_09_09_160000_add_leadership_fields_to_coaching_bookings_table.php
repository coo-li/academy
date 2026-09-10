<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coaching_bookings', function (Blueprint $table) {
            // Coaching-Kategorie: employee (Mitarbeitercoaching) oder leadership (Head-of Coaching)
            $table->string('coaching_category')->default('employee')->after('coaching_type');
            
            // Für Leadership Coaching: Stunden statt Festpreis
            $table->decimal('hours', 5, 2)->nullable()->after('cost');
            
            // Zeitraum (Von-Bis Monat)
            $table->date('start_month')->nullable()->after('booking_date');
            $table->date('end_month')->nullable()->after('start_month');
            
            // Flag ob vom Budget abgezogen wird (false für Leadership)
            $table->boolean('deduct_from_budget')->default(true)->after('hours');
            
            // Foreign Key zum Coach
            $table->foreignId('coach_id')->nullable()->after('coach_name')->constrained('coaches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coaching_bookings', function (Blueprint $table) {
            $table->dropForeign(['coach_id']);
            $table->dropColumn([
                'coaching_category',
                'hours',
                'start_month',
                'end_month',
                'deduct_from_budget',
                'coach_id',
            ]);
        });
    }
};
