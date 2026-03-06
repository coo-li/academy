<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_id' => ['required', 'exists:modules,id'],
            'training_session_id' => ['required', 'exists:training_sessions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'module_id.required' => 'Bitte wähle ein Modul aus.',
            'module_id.exists' => 'Das ausgewählte Modul existiert nicht.',
            'training_session_id.required' => 'Es muss ein konkreter Termin ausgewählt werden.',
            'training_session_id.exists' => 'Der ausgewählte Termin existiert nicht.',
        ];
    }
}
