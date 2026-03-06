<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $existing = DB::table('modules')
            ->whereNotNull('skill_category')
            ->where('skill_category', '!=', '')
            ->distinct()
            ->pluck('skill_category');

        foreach ($existing as $name) {
            DB::table('skill_categories')->insert([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('modules', function (Blueprint $table) {
            $table->foreignId('skill_category_id')->nullable()->after('type');
        });

        $categories = DB::table('skill_categories')->pluck('id', 'name');
        foreach ($categories as $name => $id) {
            DB::table('modules')
                ->where('skill_category', $name)
                ->update(['skill_category_id' => $id]);
        }

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('skill_category');
            $table->foreign('skill_category_id')->references('id')->on('skill_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['skill_category_id']);
            $table->string('skill_category')->nullable()->after('type');
        });

        $categories = DB::table('skill_categories')->pluck('name', 'id');
        foreach ($categories as $id => $name) {
            DB::table('modules')
                ->where('skill_category_id', $id)
                ->update(['skill_category' => $name]);
        }

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('skill_category_id');
        });

        Schema::dropIfExists('skill_categories');
    }
};
