<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleInterest extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'asana_task_gid',
        'noted_at',
        'noted_by',
    ];

    protected function casts(): array
    {
        return [
            'noted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function notedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'noted_by');
    }

    public function isNoted(): bool
    {
        return $this->noted_at !== null;
    }

    public function isPending(): bool
    {
        return $this->noted_at === null;
    }
}
