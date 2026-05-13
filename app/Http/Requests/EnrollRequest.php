<?php

namespace App\Http\Requests;

use App\Models\Method;
use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;

class EnrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $module = Module::with('method')->find($this->module_id);
        $schedulingType = $module?->method?->scheduling_type ?? Method::TYPE_SCHEDULED;

        $rules = [
            'module_id' => ['required', 'exists:modules,id'],
        ];

        if ($schedulingType === Method::TYPE_SCHEDULED) {
            $rules['training_session_id'] = ['required', 'exists:training_sessions,id'];
        }

        return $rules;
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
