<?php

namespace App\Http\Requests;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreTrainingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTrainer();
    }

    public function rules(): array
    {
        $rules = [
            'module_id' => ['required', 'exists:modules,id', function (string $attribute, mixed $value, \Closure $fail) {
                $user = $this->user();
                if ($user->isAdmin()) {
                    return;
                }
                $accountableIds = Module::where('accountable_type', 'user')
                    ->where('accountable_user_id', $user->id)
                    ->pluck('id');
                $pivotIds = $user->trainableModules()->pluck('modules.id');
                $allowed = $accountableIds->merge($pivotIds)->unique()->toArray();

                if (! in_array((int) $value, $allowed)) {
                    $fail('Du bist nicht als Trainer für dieses Modul eingetragen.');
                }
            }],
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date'],
            'end_time' => ['required', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'resource_email' => ['nullable', 'email', 'max:255'],
            'trainer_id' => ['nullable', 'exists:users,id'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:100'],
            'calendar_description' => ['nullable', 'string', 'max:2000'],
            'google_meet' => ['nullable', 'boolean'],
            'is_recurring' => ['nullable', 'boolean'],
        ];

        if ($this->boolean('is_recurring')) {
            $rules['frequency'] = ['required', 'in:daily,weekly,monthly'];
            $rules['frequency_interval'] = ['required', 'integer', 'min:1', 'max:12'];
            $rules['days_of_week'] = ['nullable', 'array'];
            $rules['days_of_week.*'] = ['integer', 'min:1', 'max:7'];
            $rules['series_end_date'] = ['nullable', 'date', 'after:start_date'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $startAt = $this->startAt();
            $endAt = $this->endAt();

            if ($startAt && $startAt->isPast()) {
                $validator->errors()->add('start_date', 'Der Starttermin muss in der Zukunft liegen.');
            }

            if ($startAt && $endAt && $endAt->lte($startAt)) {
                $validator->errors()->add('end_time', 'Das Ende muss nach dem Start liegen.');
            }
        });
    }

    public function startAt(): ?Carbon
    {
        if ($this->start_date && $this->start_time) {
            return Carbon::parse($this->start_date . ' ' . $this->start_time);
        }
        return null;
    }

    public function endAt(): ?Carbon
    {
        if ($this->end_date && $this->end_time) {
            return Carbon::parse($this->end_date . ' ' . $this->end_time);
        }
        return null;
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'Bitte ein Startdatum angeben.',
            'start_time.required' => 'Bitte eine Startzeit angeben.',
            'end_date.required' => 'Bitte ein Enddatum angeben.',
            'end_time.required' => 'Bitte eine Endzeit angeben.',
            'frequency.required' => 'Bitte eine Wiederholungsfrequenz wählen.',
            'frequency.in' => 'Ungültige Frequenz.',
            'series_end_date.after' => 'Das Serienende muss nach dem Startdatum liegen.',
        ];
    }
}
