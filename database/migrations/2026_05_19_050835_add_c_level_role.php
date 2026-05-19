<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->insert([
            'name' => 'C-Level',
            'slug' => 'c_level',
            'description' => 'Sieht automatisch alle Head-Ofs als direkte Reports',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'c_level')->delete();
    }
};
