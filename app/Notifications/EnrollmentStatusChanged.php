<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EnrollmentStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        protected Enrollment $enrollment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $statusLabels = [
            'attended' => 'Teilnahme bestätigt',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Storniert',
        ];

        $status = $statusLabels[$this->enrollment->status] ?? $this->enrollment->status;
        $title = $this->enrollment->module->title;

        return [
            'type' => 'enrollment',
            'module_title' => $title,
            'status' => $status,
            'message' => "Deine Anmeldung für \"{$title}\" wurde aktualisiert: {$status}",
            'url' => route('academy.module.show', $this->enrollment->module),
        ];
    }
}
