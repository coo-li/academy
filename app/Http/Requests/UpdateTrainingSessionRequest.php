<?php

namespace App\Http\Requests;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateTrainingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user->isAdmin()) {
            return true;
        }

        $session = $this->route('session');
        $module = $session->module;

        $accountableIds = Module::where('accountable_type', 'user')
            ->where('accountable_user_id', $user->id)
            ->pluck('id');
        $pivotIds = $user->trainableModules()->pluck('modules.id');
        $allowed = $accountableIds->merge($pivotIds)->unique()->toArray();

        return in_array($module->id, $allowed);
    }

    public function rules(): array
    {
        $session = $this->route('session');
        $currentEnrollments = $session->enrollments()
            ->whereIn('status', ['enrolled', 'attended', 'completed'])
            ->count();

        return [
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date'],
            'end_time' => ['required', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'resource_email' => ['nullable', 'email', 'max:255'],
            'trainer_id' => ['nullable', 'exists:users,id'],
            'max_participants' => [
                'nullable',
                'integer',
                'min:' . max(1, $currentEnrollments),
                'max:100',
            ],
            'calendar_description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $startAt = $this->startAt();
            $endAt = $this->endAt();

            if ($startAt && $startAt->isPast()) {
                $validator->errors()->add('start_date', 'Der Starttermin darf nicht in der Vergangenheit liegen.');
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
        $session = $this->route('session');
        $currentEnrollments = $session->enrollments()
            ->whereIn('status', ['enrolled', 'attended', 'completed'])
            ->count();

        return [
            'start_date.required' => 'Bitte ein Startdatum angeben.',
            'start_time.required' => 'Bitte eine Startzeit angeben.',
            'end_date.required' => 'Bitte ein Enddatum angeben.',
            'end_time.required' => 'Bitte eine Endzeit angeben.',
            'max_participants.min' => "Es sind bereits {$currentEnrollments} Teilnehmer eingebucht. Die maximale Teilnehmerzahl darf nicht darunter liegen.",
        ];
    }
}
