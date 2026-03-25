<?php

namespace App\Http\Requests;

use App\Models\Milestone;
use Illuminate\Foundation\Http\FormRequest;

class StoreMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isManager();
    }

    public function rules(): array
    {
        return [
            'career_level_id' => ['required', 'exists:career_levels,id'],
            'team_id'         => ['nullable', 'exists:teams,id'],
            'category'        => ['required', 'in:' . implode(',', array_keys(Milestone::CATEGORIES))],
            'title'           => ['required', 'string', 'max:500'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'type'            => ['required', 'in:' . implode(',', array_keys(Milestone::TYPES))],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
        ];
    }
}
