<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Method extends Model
{
    public const TYPE_SCHEDULED = 'scheduled';
    public const TYPE_SELF_STUDY = 'self_study';
    public const TYPE_REQUEST = 'request';

    public const SCHEDULING_TYPES = [
        self::TYPE_SCHEDULED,
        self::TYPE_SELF_STUDY,
        self::TYPE_REQUEST,
    ];

    public const SCHEDULING_LABELS = [
        self::TYPE_SCHEDULED => 'Terminpflichtig',
        self::TYPE_SELF_STUDY => 'Selbststudium',
        self::TYPE_REQUEST => 'Terminanfrage',
    ];

    protected $fillable = [
        'name',
        'description',
        'scheduling_type',
    ];

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public function isScheduled(): bool
    {
        return $this->scheduling_type === self::TYPE_SCHEDULED;
    }

    public function isSelfStudy(): bool
    {
        return $this->scheduling_type === self::TYPE_SELF_STUDY;
    }

    public function isRequest(): bool
    {
        return $this->scheduling_type === self::TYPE_REQUEST;
    }

    public function schedulingLabel(): string
    {
        return self::SCHEDULING_LABELS[$this->scheduling_type] ?? $this->scheduling_type;
    }
}
