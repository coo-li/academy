<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PortfolioUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_id' => ['required', 'exists:modules,id'],
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Bitte wähle eine Datei aus.',
            'file.max' => 'Die Datei darf maximal 10 MB groß sein.',
            'file.mimes' => 'Erlaubte Formate: JPG, PNG, GIF, WebP, PDF.',
        ];
    }
}
