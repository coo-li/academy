<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\TrainingMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TrainerSchulungController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $modules = $this->trainerModules()
            ->with(['skillCategory', 'method', 'trainingMaterials', 'careerLevel.careerPath', 'accountableUser'])
            ->withCount(['trainingSessions', 'enrollments'])
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->careerLevel?->careerPath?->name ?? "\xFF") <=> ($b->careerLevel?->careerPath?->name ?? "\xFF"),
                fn ($a, $b) => ($a->careerLevel?->level_number ?? PHP_INT_MAX) <=> ($b->careerLevel?->level_number ?? PHP_INT_MAX),
                fn ($a, $b) => $a->title <=> $b->title,
            ])
            ->values();

        $modulesJson = $modules->map(fn (Module $m) => [
            'id'              => $m->id,
            'title'           => $m->title,
            'url'             => route('trainer.schulungen.show', $m),
            'path'            => $m->careerLevel?->careerPath?->name ?? '',
            'pathEmoji'       => $m->careerLevel?->careerPath?->emoji ?? '',
            'pathId'          => $m->careerLevel?->careerPath?->id,
            'level'           => $m->careerLevel?->title ?? '',
            'levelNumber'     => $m->careerLevel?->level_number ?? 9999,
            'skillGroup'      => $m->skillCategory?->name ?? '',
            'skillGroupEmoji' => $m->skillCategory?->emoji ?? '',
            'trainer'         => $m->accountableUser?->name ?? '',
            'method'          => $m->method?->name ?? '',
            'sessions'        => $m->training_sessions_count,
            'enrollments'     => $m->enrollments_count,
            'materials'       => $m->trainingMaterials->count(),
        ]);

        $isAdmin = $user->isAdmin();

        return view('trainer.schulungen', compact('modules', 'modulesJson', 'isAdmin'));
    }

    public function show(Module $module)
    {
        $this->authorizeModule($module);

        $module->load(['skillCategory', 'method', 'trainingMaterials.uploader', 'trainingSessions.enrollments', 'quiz']);

        return view('trainer.schulung-detail', compact('module'));
    }

    public function update(Request $request, Module $module)
    {
        $this->authorizeModule($module);

        $request->validate([
            'description' => ['nullable', 'string', 'max:5000'],
            'calendar_description' => ['nullable', 'string', 'max:2000'],
        ]);

        $module->update($request->only('description', 'calendar_description'));

        return back()->with('success', 'Schulungsbeschreibung aktualisiert.');
    }

    public function storeMaterial(Request $request, Module $module)
    {
        $this->authorizeModule($module);

        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,ppt,pptx,xls,xlsx'],
        ]);

        $file = $request->file('file');
        $user = Auth::user();

        $path = $file->store(
            "training-materials/{$module->id}",
            'gcs'
        );

        TrainingMaterial::create([
            'module_id' => $module->id,
            'uploaded_by' => $user->id,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'Unterlage erfolgreich hochgeladen.');
    }

    public function storeQuiz(Request $request, Module $module)
    {
        $this->authorizeModule($module);

        $request->validate([
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*' => ['required', 'string'],
            'questions.*.correct' => ['required', 'integer', 'min:0'],
            'pass_percentage' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $module->quiz()->updateOrCreate(
            ['module_id' => $module->id],
            [
                'questions' => $request->questions,
                'pass_percentage' => $request->pass_percentage,
            ]
        );

        return back()->with('success', 'Quiz gespeichert!');
    }

    public function storeLink(Request $request, Module $module)
    {
        $this->authorizeModule($module);

        $request->validate([
            'url' => ['required', 'url', 'max:2000'],
            'link_title' => ['nullable', 'string', 'max:255'],
        ]);

        TrainingMaterial::create([
            'module_id' => $module->id,
            'uploaded_by' => Auth::id(),
            'type' => 'link',
            'url' => $request->url,
            'link_title' => $request->link_title,
        ]);

        return back()->with('success', 'Link erfolgreich hinzugefügt.');
    }

    public function destroyMaterial(TrainingMaterial $material)
    {
        $module = $material->module;
        $this->authorizeModule($module);

        if ($material->isFile() && $material->storage_path) {
            Storage::disk('gcs')->delete($material->storage_path);
        }

        $material->delete();

        return back()->with('success', 'Unterlage gelöscht.');
    }

    private function trainerModules()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return Module::query();
        }

        return Module::where('accountable_type', 'user')
            ->where('accountable_user_id', $user->id);
    }

    private function authorizeModule(Module $module): void
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($module->accountable_type !== 'user' || $module->accountable_user_id !== $user->id) {
            abort(403, 'Du bist nicht als Trainer für dieses Modul eingetragen.');
        }
    }
}
