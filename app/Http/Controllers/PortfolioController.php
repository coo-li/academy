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
                  ->orWhere('link_title', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhereHas('module', fn ($m) => $m->where('title', 'like', "%{$search}%"));
            });
        }

        $trainingMaterials = $trainingMaterialsQuery->orderBy('created_at', 'desc')->get();
        $groupedMaterials = $trainingMaterials->groupBy('module_id');

        return view('portfolio.index', compact('uploads', 'groupedUploads', 'modules', 'trainingMaterials', 'groupedMaterials'));
    }

    public function store(PortfolioUploadRequest $request)
    {
        $user = Auth::user();
        $file = $request->file('file');

        $data = [
            'user_id' => $user->id,
            'module_id' => $request->module_id,
            'notes' => $request->notes,
        ];

        if ($file) {
            $path = $file->store(
                "portfolio/{$user->id}/{$request->module_id}",
                'gcs'
            );

            $data['original_filename'] = $file->getClientOriginalName();
            $data['storage_path'] = $path;
            $data['mime_type'] = $file->getMimeType();
            $data['file_size'] = $file->getSize();
        } else {
            $data['original_filename'] = 'Notiz';
            $data['storage_path'] = '';
            $data['mime_type'] = 'text/plain';
            $data['file_size'] = 0;
        }

        PortfolioUpload::create($data);

        $message = $file ? 'Datei erfolgreich hochgeladen!' : 'Notiz erfolgreich gespeichert!';

        return back()->with('success', $message);
    }

    public function preview(PortfolioUpload $upload)
    {
        if ($upload->user_id !== Auth::id() && ! Auth::user()->isTrainer() && ! Auth::user()->isManager()) {
            abort(403);
        }

        if ($upload->storage_path === '') {
            abort(404);
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

        if ($upload->storage_path === '') {
            abort(404);
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

        if ($upload->storage_path !== '') {
            Storage::disk('gcs')->delete($upload->storage_path);
        }
        $upload->delete();

        return back()->with('success', $upload->storage_path !== '' ? 'Datei gelöscht.' : 'Notiz gelöscht.');
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

        if ($material->isLink()) {
            return redirect($material->url);
        }

        $url = Storage::disk('gcs')->temporaryUrl($material->storage_path, now()->addMinutes(5), [
            'response-content-disposition' => 'attachment; filename="' . $material->original_filename . '"',
        ]);

        return redirect($url);
    }
}
