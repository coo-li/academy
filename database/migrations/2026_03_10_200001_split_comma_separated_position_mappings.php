<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mappings = DB::table('personio_position_mappings')
            ->where('personio_path_raw', 'LIKE', '%,%')
            ->get();

        foreach ($mappings as $mapping) {
            $paths = array_map('trim', explode(',', $mapping->personio_path_raw));

            foreach ($paths as $path) {
                DB::table('personio_position_mappings')->insertOrIgnore([
                    'personio_position' => $mapping->personio_position,
                    'personio_level_raw' => $mapping->personio_level_raw,
                    'personio_path_raw' => $path,
                    'career_path_id' => null,
                    'career_level_id' => null,
                    'is_auto_matched' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('personio_position_mappings')
                ->where('id', $mapping->id)
                ->delete();
        }
    }

    public function down(): void
    {
        // Reverse split is not reliably possible
    }
};
