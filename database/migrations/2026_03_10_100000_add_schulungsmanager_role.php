<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->insert([
            'name' => 'Schulungsmanager',
            'slug' => 'schulungsmanager',
            'description' => 'Anlegen und Verwalten von Schulungen, Karrierepfaden, Skill-Kategorien und Methoden',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'schulungsmanager')->delete();
    }
};
