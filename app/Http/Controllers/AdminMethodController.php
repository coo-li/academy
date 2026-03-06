<?php

namespace App\Http\Controllers;

use App\Models\Method;
use Illuminate\Http\Request;

class AdminMethodController extends Controller
{
    public function index()
    {
        // #region agent log
        $u = auth()->user();
        file_put_contents('/var/www/html/.cursor/debug-f4ed0e.log', json_encode(['sessionId'=>'f4ed0e','hypothesisId'=>'H4','location'=>'AdminMethodController.php:index','message'=>'Methods page accessed','data'=>['user_id'=>$u->id,'user_name'=>$u->name,'role_slugs'=>$u->roles->pluck('slug')->toArray(),'isTeacher'=>$u->isTeacher(),'isManager'=>$u->isManager(),'isAdmin'=>$u->isAdmin()],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND);
        // #endregion

        $methods = Method::withCount('modules')
            ->orderBy('name')
            ->get();

        return view('admin.methods.index', compact('methods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:methods,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Method::create($request->only('name', 'description'));

        return redirect()
            ->route('admin.methods.index')
            ->with('success', "Methode \"{$request->name}\" wurde erstellt.");
    }

    public function update(Request $request, Method $method)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:methods,name,' . $method->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $method->update($request->only('name', 'description'));

        return redirect()
            ->route('admin.methods.index')
            ->with('success', "Methode \"{$method->name}\" wurde aktualisiert.");
    }

    public function destroy(Method $method)
    {
        $name = $method->name;
        $moduleCount = $method->modules()->count();

        if ($moduleCount > 0) {
            $method->modules()->update(['method_id' => null]);
        }

        $method->delete();

        return redirect()
            ->route('admin.methods.index')
            ->with('success', "Methode \"{$name}\" wurde gelöscht." .
                ($moduleCount > 0 ? " {$moduleCount} Module wurden entkoppelt." : ''));
    }
}
