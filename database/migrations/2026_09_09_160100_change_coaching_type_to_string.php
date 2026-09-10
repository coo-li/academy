<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change enum to string to allow 'leadership' type
        DB::statement("ALTER TABLE coaching_bookings MODIFY COLUMN coaching_type VARCHAR(50) NOT NULL DEFAULT 'full'");
    }

    public function down(): void
    {
        // Revert to enum (will fail if 'leadership' values exist)
        DB::statement("ALTER TABLE coaching_bookings MODIFY COLUMN coaching_type ENUM('full', 'quick_help') NOT NULL DEFAULT 'full'");
    }
};
