<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // #region agent log
        @file_put_contents('/tmp/debug-33f870.log', json_encode(['sessionId'=>'33f870','location'=>'SearchController:__invoke','message'=>'search hit','data'=>['q'=>$request->get('q')],'timestamp'=>round(microtime(true)*1000),'runId'=>'post-fix','hypothesisId'=>'B'])."\n", FILE_APPEND);
        // #endregion
        $q = trim($request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%' . $q . '%';

        $modules = Module::with(['careerLevel.careerPath', 'skillCategory'])
            ->where(fn ($query) => $query
                ->where('title', 'like', $like)
                ->orWhere('description', 'like', $like)
            )
            ->limit(8)
            ->get()
            ->map(fn (Module $m) => [
                'title' => $m->title,
                'subtitle' => collect([
                    $m->careerLevel?->careerPath?->name,
                    $m->careerLevel?->title,
                    $m->skillCategory?->name,
                ])->filter()->implode(' · '),
                'url' => route('academy.module.show', $m),
            ]);

        return response()->json(['modules' => $modules]);
    }
}
