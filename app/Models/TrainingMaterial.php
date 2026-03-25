<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingMaterial extends Model
{
    protected $fillable = [
        'module_id',
        'uploaded_by',
        'type',
        'url',
        'link_title',
        'original_filename',
        'storage_path',
        'mime_type',
        'file_size',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isLink(): bool
    {
        return $this->type === 'link';
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    public function displayName(): string
    {
        if ($this->isLink()) {
            return $this->link_title ?: $this->url;
        }

        return $this->original_filename;
    }
}
