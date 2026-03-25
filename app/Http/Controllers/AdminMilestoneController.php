<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMilestoneRequest;
use App\Models\CareerLevel;
use App\Models\Milestone;
use App\Models\Team;
use Illuminate\Http\Request;

class AdminMilestoneController extends Controller
{
    public function index(Request $request)
    {
        $teams = Team::orderBy('name')->get();
        $selectedTeamId = $request->get('team');

        $milestonesByLevel = collect();

        if ($selectedTeamId) {
            $team = Team::findOrFail($selectedTeamId);

            $milestones = Milestone::with(['careerLevel.careerPath', 'team'])
                ->where(fn ($q) => $q->where('team_id', $selectedTeamId)->orWhereNull('team_id'))
                ->orderBy('career_level_id')
                ->orderBy('sort_order')
                ->get();

            $milestonesByLevel = $milestones->groupBy('career_level_id')
                ->sortBy(fn ($items) => $this->levelSortKey($items->first()->careerLevel));
        }

        $levelCounts = Milestone::selectRaw('career_level_id, count(*) as cnt')
            ->when($selectedTeamId, fn ($q) => $q->where(fn ($q2) => $q2->where('team_id', $selectedTeamId)->orWhereNull('team_id')))
            ->groupBy('career_level_id')
            ->pluck('cnt', 'career_level_id');

        return view('admin.milestones.index', compact(
            'teams', 'selectedTeamId', 'milestonesByLevel', 'levelCounts'
        ));
    }

    public function create(Request $request)
    {
        $levels = CareerLevel::with('careerPath')
            ->get()
            ->groupBy(fn ($l) => $l->careerPath->name);

        $teams = Team::orderBy('name')->get();

        return view('admin.milestones.create', compact('levels', 'teams'));
    }

    public function store(StoreMilestoneRequest $request)
    {
        Milestone::create($request->validated());

        return redirect()
            ->route('admin.milestones.index', ['team' => $request->team_id ?? session('last_team_filter')])
            ->with('success', 'Milestone erstellt.');
    }

    public function edit(Milestone $milestone)
    {
        $levels = CareerLevel::with('careerPath')
            ->get()
            ->groupBy(fn ($l) => $l->careerPath->name);

        $teams = Team::orderBy('name')->get();

        return view('admin.milestones.edit', compact('milestone', 'levels', 'teams'));
    }

    public function update(StoreMilestoneRequest $request, Milestone $milestone)
    {
        $milestone->update($request->validated());

        return redirect()
            ->route('admin.milestones.index', ['team' => $milestone->team_id ?? session('last_team_filter')])
            ->with('success', "Milestone \"{$milestone->title}\" aktualisiert.");
    }

    public function destroy(Milestone $milestone)
    {
        $teamId = $milestone->team_id;
        $milestone->delete();

        return redirect()
            ->route('admin.milestones.index', ['team' => $teamId ?? session('last_team_filter')])
            ->with('success', 'Milestone gelöscht.');
    }

    private function levelSortKey(CareerLevel $level): string
    {
        $pathOrder = match ($level->careerPath->name) {
            'td Allgemein' => '1',
            'Leadership' => '2',
            'Account Management' => '3',
            'Expert (Digitalstrategie)' => '4',
            default => '9',
        };

        return $pathOrder . '.' . $level->level_number;
    }
}
