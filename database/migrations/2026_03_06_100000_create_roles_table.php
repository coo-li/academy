<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Vollzugriff auf alle Bereiche', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'People Manager', 'slug' => 'people_manager', 'description' => 'Verwaltung von Mitarbeitenden', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Head of', 'slug' => 'head_of', 'description' => 'Abteilungsleitung', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trainer', 'slug' => 'trainer', 'description' => 'Zugriff auf Lehrer-Konsole', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mitarbeitender', 'slug' => 'mitarbeitender', 'description' => 'Basis-Zugriff auf Lern-Bereich', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
