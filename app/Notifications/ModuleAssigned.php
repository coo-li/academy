<?php

namespace App\Notifications;

use App\Models\Module;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ModuleAssigned extends Notification
{
    use Queueable;

    public function __construct(
        protected Module $module,
        protected User $assignedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'assignment',
            'module_title' => $this->module->title,
            'assigned_by' => $this->assignedBy->name,
            'message' => "\"{$this->module->title}\" wurde dir von {$this->assignedBy->name} zugewiesen.",
            'url' => route('academy.module.show', $this->module),
        ];
    }
}
