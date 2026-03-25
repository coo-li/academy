<?php

namespace Database\Seeders;

use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    private const PASSWORD = 'TestUser2026!';

    public function run(): void
    {
        $roles = Role::all()->keyBy('slug');

        $careerLevels = $this->resolveCareerLevels();

        $testUsers = [
            [
                'name' => 'Jannika Test',
                'email' => 'jannika.test@trafficdesign.de',
                'roles' => ['schulungsmanager'],
                'career_level' => null,
            ],
            [
                'name' => 'Linda Test',
                'email' => 'linda.test@trafficdesign.de',
                'roles' => ['mitarbeitender'],
                'career_level' => null,
            ],
            [
                'name' => 'Greta Test',
                'email' => 'greta.test@trafficdesign.de',
                'roles' => ['mitarbeitender'],
                'career_level' => null,
            ],
            [
                'name' => 'Vanessa Test',
                'email' => 'vanessa.test@trafficdesign.de',
                'roles' => ['mitarbeitender'],
                'career_level' => 'td_allgemein_junior',
            ],
            [
                'name' => 'Güney Test',
                'email' => 'gueney.test@trafficdesign.de',
                'roles' => ['mitarbeitender'],
                'career_level' => 'td_allgemein_junior',
            ],
            [
                'name' => 'Chris Test',
                'email' => 'chris.test@trafficdesign.de',
                'roles' => ['mitarbeitender'],
                'career_level' => 'td_allgemein_professional',
            ],
            [
                'name' => 'Jan Test',
                'email' => 'jan.test@trafficdesign.de',
                'roles' => ['mitarbeitender'],
                'career_level' => 'td_allgemein_professional',
            ],
            [
                'name' => 'Nils Test',
                'email' => 'nils.test@trafficdesign.de',
                'roles' => ['mitarbeitender'],
                'career_level' => 'expert_specialist',
            ],
            [
                'name' => 'Elisa Test',
                'email' => 'elisa.test@trafficdesign.de',
                'roles' => ['mitarbeitender'],
                'career_level' => 'leadership_specialist',
            ],
            [
                'name' => 'Laura Test',
                'email' => 'laura.test@trafficdesign.de',
                'roles' => ['mitarbeitender'],
                'career_level' => 'account_management_senior',
            ],
            [
                'name' => 'Helena Test',
                'email' => 'helena.test@trafficdesign.de',
                'roles' => ['mitarbeitender', 'head_of', 'trainer'],
                'career_level' => null,
            ],
            [
                'name' => 'Anke Test',
                'email' => 'anke.test@trafficdesign.de',
                'roles' => ['mitarbeitender', 'head_of'],
                'career_level' => null,
            ],
            [
                'name' => 'Jenny Test',
                'email' => 'jenny.test@trafficdesign.de',
                'roles' => ['mitarbeitender', 'people_manager'],
                'career_level' => null,
            ],
            [
                'name' => 'Dani Test',
                'email' => 'dani.test@trafficdesign.de',
                'roles' => ['mitarbeitender', 'schulungsmanager'],
                'career_level' => null,
            ],
            [
                'name' => 'Bene Test',
                'email' => 'bene.test@trafficdesign.de',
                'roles' => ['admin', 'mitarbeitender'],
                'career_level' => null,
            ],
            [
                'name' => 'Freddy Test',
                'email' => 'freddy.test@trafficdesign.de',
                'roles' => ['mitarbeitender', 'trainer'],
                'career_level' => null,
            ],
        ];

        foreach ($testUsers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'role' => in_array('admin', $data['roles']) ? 'admin' : 'student',
                    'email_verified_at' => now(),
                ]
            );

            $roleIds = collect($data['roles'])
                ->map(fn (string $slug) => $roles->get($slug)?->id)
                ->filter()
                ->all();

            $user->roles()->sync($roleIds);

            if ($data['career_level'] && isset($careerLevels[$data['career_level']])) {
                $user->addCareerLevel($careerLevels[$data['career_level']]);
            }

            $this->command->info("  {$data['name']} ({$data['email']}) — Rollen: " . implode(', ', $data['roles']));
        }

        $this->command->info('');
        $this->command->info(count($testUsers) . ' Testuser erfolgreich angelegt/aktualisiert.');
    }

    private function resolveCareerLevels(): array
    {
        $levels = [];

        $tdAllgemein = CareerPath::where('name', 'like', '%Allgemein%')->first();
        if ($tdAllgemein) {
            $levels['td_allgemein_junior'] = CareerLevel::where('career_path_id', $tdAllgemein->id)
                ->where('title', 'Junior')->first();
            $levels['td_allgemein_professional'] = CareerLevel::where('career_path_id', $tdAllgemein->id)
                ->where('title', 'Professional')->first();
        }

        $leadership = CareerPath::where('name', 'Leadership')->first();
        if ($leadership) {
            $levels['leadership_specialist'] = CareerLevel::where('career_path_id', $leadership->id)
                ->where('title', 'Specialist')->first();
        }

        $accountMgmt = CareerPath::where('name', 'Account Management')->first();
        if ($accountMgmt) {
            $levels['account_management_senior'] = CareerLevel::where('career_path_id', $accountMgmt->id)
                ->where('title', 'Senior')->first();
        }

        $expert = CareerPath::where('name', 'like', '%Digitalstrategie%')->first();
        if ($expert) {
            $levels['expert_specialist'] = CareerLevel::where('career_path_id', $expert->id)
                ->where('title', 'Specialist')->first();
        }

        return array_filter($levels);
    }
}
