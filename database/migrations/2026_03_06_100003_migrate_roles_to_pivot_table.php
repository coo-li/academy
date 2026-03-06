<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleMapping = [
            'admin' => 'admin',
            'manager' => 'people_manager',
            'teacher' => 'trainer',
            'student' => 'mitarbeitender',
        ];

        $roleSlugs = DB::table('roles')->pluck('id', 'slug');

        $users = DB::table('users')->whereNotNull('role')->get(['id', 'role']);

        foreach ($users as $user) {
            $slug = $roleMapping[$user->role] ?? 'mitarbeitender';
            $roleId = $roleSlugs[$slug] ?? $roleSlugs['mitarbeitender'];

            DB::table('role_user')->insertOrIgnore([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('role_user')->truncate();
    }
};
