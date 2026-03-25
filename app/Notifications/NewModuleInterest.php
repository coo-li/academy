<?php

namespace App\Notifications;

use App\Models\Module;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewModuleInterest extends Notification
{
    use Queueable;

    public function __construct(
        protected Module $module,
        protected User $interestedUser
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'interest',
            'module_title' => $this->module->title,
            'user_name' => $this->interestedUser->name,
            'message' => "{$this->interestedUser->name} hat Interesse an \"{$this->module->title}\" bekundet.",
            'url' => route('manage.employees.show', $this->interestedUser),
        ];
    }
}
