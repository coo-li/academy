<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_bookings', function (Blueprint $table) {
            // Neue Kosten-Felder
            $table->decimal('cost_gross', 10, 2)->nullable()->after('net_cost');
            $table->decimal('travel_costs', 10, 2)->nullable()->after('cost_gross');
            $table->decimal('accommodation_costs', 10, 2)->nullable()->after('travel_costs');
            $table->decimal('other_costs', 10, 2)->nullable()->after('accommodation_costs');
            
            // Datum und Status
            $table->date('booking_date')->nullable()->after('name');
            $table->string('status')->default('pending')->after('booking_date');
            
            // Zusatzinfos
            $table->string('website')->nullable()->after('notes');
            $table->string('order_number')->nullable()->after('website');
            
            // C-Level Genehmigung
            $table->boolean('clevel_approved')->default(false)->after('order_number');
            $table->foreignId('clevel_approved_by')->nullable()->constrained('users')->after('clevel_approved');
            $table->timestamp('clevel_approved_at')->nullable()->after('clevel_approved_by');
            
            // Index
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('training_bookings', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropForeign(['clevel_approved_by']);
            $table->dropColumn([
                'cost_gross',
                'travel_costs',
                'accommodation_costs',
                'other_costs',
                'booking_date',
                'status',
                'website',
                'order_number',
                'clevel_approved',
                'clevel_approved_by',
                'clevel_approved_at',
            ]);
        });
    }
};
