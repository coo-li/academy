<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioUploadRequest;
use App\Models\Module;
use App\Models\PortfolioUpload;
use App\Models\TrainingMaterial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = $user->portfolioUploads()->with('module');

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('module', fn ($m) => $m->where('title', 'like', "%{$search}%"));
            });
        }

        $uploads = $query->orderBy('created_at', 'desc')->get();
        $groupedUploads = $uploads->groupBy('module_id');
        $modules = Module::orderBy('title')->get();

        $attendedModuleIds = $user->enrollments()
            ->whereIn('status', ['attended', 'completed'])
            ->pluck('module_id')
            ->unique();

        $trainingMaterialsQuery = TrainingMaterial::with('module')
            ->whereIn('module_id', $attendedModuleIds);

        if ($search) {
            $trainingMaterialsQuery->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhereHas('module', fn ($m) => $m->where('title', 'like', "%{$search}%"));
            });
        }

        $trainingMaterials = $trainingMaterialsQuery->orderBy('created_at', 'desc')->get();
        $groupedMaterials = $trainingMaterials->groupBy('module_id');

        return view('portfolio.index', compact('uploads', 'groupedUploads', 'modules', 'trainingMaterials', 'groupedMaterials'));
    }

    public function store(PortfolioUploadRequest $request)
    {
        $file = $request->file('file');
        $user = Auth::user();

        $path = $file->store(
            "portfolio/{$user->id}/{$request->module_id}",
            'gcs'
        );

        PortfolioUpload::create([
            'user_id' => $user->id,
            'module_id' => $request->module_id,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Datei erfolgreich hochgeladen!');
    }

    public function preview(PortfolioUpload $upload)
    {
        if ($upload->user_id !== Auth::id() && ! Auth::user()->isTrainer() && ! Auth::user()->isManager()) {
            abort(403);
        }

        $url = Storage::disk('gcs')->temporaryUrl($upload->storage_path, now()->addMinutes(15));

        return response()->json([
            'url' => $url,
            'filename' => $upload->original_filename,
            'mime_type' => $upload->mime_type,
        ]);
    }

    public function download(PortfolioUpload $upload)
    {
        if ($upload->user_id !== Auth::id() && ! Auth::user()->isTrainer() && ! Auth::user()->isManager()) {
            abort(403);
        }

        $url = Storage::disk('gcs')->temporaryUrl($upload->storage_path, now()->addMinutes(5), [
            'response-content-disposition' => 'attachment; filename="' . $upload->original_filename . '"',
        ]);

        return redirect($url);
    }

    public function destroy(PortfolioUpload $upload)
    {
        if ($upload->user_id !== Auth::id() && ! Auth::user()->isTrainer() && ! Auth::user()->isManager()) {
            abort(403);
        }

        Storage::disk('gcs')->delete($upload->storage_path);
        $upload->delete();

        return back()->with('success', 'Datei gelöscht.');
    }

    public function downloadMaterial(TrainingMaterial $material)
    {
        $user = Auth::user();

        $hasAccess = $user->isAdmin()
            || $user->isTrainer()
            || $user->enrollments()
                ->where('module_id', $material->module_id)
                ->whereIn('status', ['attended', 'completed'])
                ->exists();

        if (! $hasAccess) {
            abort(403);
        }

        $url = Storage::disk('gcs')->temporaryUrl($material->storage_path, now()->addMinutes(5), [
            'response-content-disposition' => 'attachment; filename="' . $material->original_filename . '"',
        ]);

        return redirect($url);
    }
}
