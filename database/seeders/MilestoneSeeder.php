<?php

namespace Database\Seeders;

use App\Models\CareerLevel;
use App\Models\Milestone;
use App\Models\Team;
use Illuminate\Database\Seeder;

class MilestoneSeeder extends Seeder
{
    private array $levelCache = [];
    private array $teamCache = [];

    public function run(): void
    {
        Milestone::query()->delete();

        $this->buildCaches();

        $this->seedJunior();
        $this->seedProfessional();
        $this->seedSpecialistLeadership();
        $this->seedSpecialistAM();
        $this->seedSpecialistExpert();
    }

    private function buildCaches(): void
    {
        $this->levelCache = CareerLevel::with('careerPath')->get()
            ->mapWithKeys(fn ($l) => [$l->careerPath->name . '|' . $l->level_number => $l->id])
            ->toArray();

        $this->teamCache = Team::pluck('id', 'name')->toArray();
    }

    private function levelId(string $path, int $number): int
    {
        return $this->levelCache[$path . '|' . $number]
            ?? throw new \RuntimeException("CareerLevel not found: {$path} #{$number}");
    }

    private function teamId(string $name): int
    {
        return $this->teamCache[$name]
            ?? throw new \RuntimeException("Team not found: {$name}");
    }

    /**
     * Sheet "Milestones für einen Junior" -> td Allgemein #1
     */
    private function seedJunior(): void
    {
        $levelId = $this->levelId('td Allgemein', 1);

        $this->create($levelId, null, [
            ['Budgettreue und Erreichen Ziel-Stundensatz 140€', 'passiv'],
            ['Voll-Auslastung / Projektstunden-Zielerreichung', 'passiv'],
            ['1.200 Projektstunden Erfahrung', 'passiv'],
            ['Eigenständig Strategie-Vorschläge für 3 B/C Kunden erarbeiten und einbringen', 'passiv'],
        ]);

        $this->create($levelId, 'Creation', [
            ['Übernahme Kunden als CM', 'passiv'],
        ]);

        $smopmVariant = [['Übernahme Kunden als Accountable CM', 'passiv']];
        $this->create($levelId, 'Social Media Organic', $smopmVariant);
        $this->create($levelId, 'Social Media Ads', $smopmVariant);
        $this->create($levelId, 'Google Ads', $smopmVariant);
    }

    /**
     * Sheet "Milestones für einen Profession" -> td Allgemein #2
     */
    private function seedProfessional(): void
    {
        $levelId = $this->levelId('td Allgemein', 2);

        $this->create($levelId, null, [
            ['Budgettreue und Erreichen Ziel-Stundensatz 150€', 'passiv'],
            ['Voll-Auslastung / Projektstunden-Zielerreichung', 'passiv'],
            ['Neukunden onborden', 'passiv'],
            ['Eigenständig Strategie-Vorschläge für 3 A/B-Kunden erarbeiten und einbringen', 'passiv'],
            ['Übernahme Multichannel Kunden als Accountable CM', 'passiv'],
            ['Übernahme Single Channel als CM und AM', 'passiv'],
            ['Up- oder Crossselling bei Bestandskunden erkennen und einbringen', 'passiv'],
        ]);

        $this->create($levelId, 'Creation', [
            ['Erfolgreiche Testphase abschließen', 'passiv'],
        ]);
        $this->create($levelId, 'Social Media Organic', [
            ['Pitstop-Gespräch nach 3 Monaten führen', 'passiv'],
        ]);
        $pmVariant = [['Erfolgreiche Testphase abschließen', 'passiv']];
        $this->create($levelId, 'Social Media Ads', $pmVariant);
        $this->create($levelId, 'Google Ads', $pmVariant);
    }

    /**
     * Sheet "Milestones fürSpecialist Leader" -> Leadership #1
     */
    private function seedSpecialistLeadership(): void
    {
        $levelId = $this->levelId('Leadership', 1);

        $this->create($levelId, null, [
            ['Budgettreue und Erreichen Ziel-Stundensatz 160€', 'passiv'],
            ['Voll-Auslastung / Projektstunden-Zielerreichung', 'passiv'],
            ['Als Digitalstratege in 3 Projekten strategischen Input erarbeiten und einbringen', 'passiv'],
            ['Übernahme A Multichannel Kunden als CM Senior Part im Junior-Senior Tag Team (Delegieren)', 'passiv'],
            ['Up- oder Crossselling 10% bei 3 Kunden umsetzen', 'passiv'],
            ['Trainee-Betreuung (inkl. Auswahl, Onboarding & Feedback)', 'aktiv'],
            ['Onboarding eines Juniors / Professionals durchführen', 'aktiv'],
        ]);
    }

    /**
     * Sheet "Milestones für Specialist AM" -> Account Management #1
     */
    private function seedSpecialistAM(): void
    {
        $levelId = $this->levelId('Account Management', 1);

        $this->create($levelId, null, [
            ['Budgettreue und Erreichen Ziel-Stundensatz 160€', 'passiv'],
            ['Voll-Auslastung / Projektstunden-Zielerreichung', 'passiv'],
            ['Strategie/Potenziale erkennen & umsetzen (ggf. im DS-Tag-Team)', 'passiv'],
            ['Übernahme Accountmanager A Multichannel Kunden', 'passiv'],
            ['Upselling 10% bei 3 Kunden umsetzen', 'passiv'],
            ['Crossselling 10% bei 3 Kunden umsetzen', 'passiv'],
        ]);
    }

    /**
     * Sheet "Milestones fü Specialist Expert" -> Expert (Digitalstrategie) #1
     */
    private function seedSpecialistExpert(): void
    {
        $levelId = $this->levelId('Expert (Digitalstrategie)', 1);

        $this->create($levelId, null, [
            ['Budgettreue und Erreichen Ziel-Stundensatz 160€', 'passiv'],
            ['Voll-Auslastung / Projektstunden-Zielerreichung', 'passiv'],
            ['Als Digitalstratege in 3 Projekten strategischen Input erarbeiten und einbringen', 'passiv'],
            ['Übernahme A Multichannel Kunden als Accountable CM', 'passiv'],
            ['Up- oder Crossselling 10% bei 3 Kunden umsetzen', 'passiv'],
        ]);
    }

    /**
     * @param array<array{0: string, 1: string}> $items [[title, type], ...]
     */
    private function create(int $levelId, ?string $teamName, array $items): void
    {
        $teamId = $teamName ? $this->teamId($teamName) : null;

        foreach ($items as $sort => $item) {
            Milestone::create([
                'career_level_id' => $levelId,
                'team_id' => $teamId,
                'category' => 'spezifisch',
                'title' => $item[0],
                'type' => $item[1],
                'sort_order' => $sort + 1,
            ]);
        }
    }
}
