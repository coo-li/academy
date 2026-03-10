<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSchulungsmanager();
    }

    public function rules(): array
    {
        return [
            'career_level_id' => ['nullable', 'exists:career_levels,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'skill_category_id' => ['nullable', 'exists:skill_categories,id'],
            'method_id' => ['nullable', 'exists:methods,id'],
            'accountable_type' => ['nullable', 'in:user,head_of'],
            'accountable_user_id' => ['nullable', 'required_if:accountable_type,user', 'exists:users,id'],
            'is_mandatory' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->accountable_type === 'head_of') {
            $this->merge(['accountable_user_id' => null]);
        }
    }
}
