<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PortfolioUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hasNotes = filled($this->input('notes'));

        return [
            'module_id' => ['required', 'exists:modules,id', function ($attribute, $value, $fail) {
                $effectiveIds = Auth::user()->effectiveModules()->pluck('id')->all();

                if (! in_array((int) $value, $effectiveIds)) {
                    $fail('Du bist diesem Modul nicht zugewiesen.');
                }
            }],
            'file' => [$hasNotes ? 'nullable' : 'required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
            'notes' => [$this->hasFile('file') ? 'nullable' : 'required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Bitte wähle eine Datei aus oder fülle das Notiz-Feld aus.',
            'file.max' => 'Die Datei darf maximal 10 MB groß sein.',
            'file.mimes' => 'Erlaubte Formate: JPG, PNG, GIF, WebP, PDF.',
            'notes.required' => 'Bitte fülle eine Notiz aus oder lade eine Datei hoch.',
        ];
    }
}
