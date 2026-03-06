<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Method extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }
}
