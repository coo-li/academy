<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CareerPath extends Model
{
    protected $fillable = [
        'name',
        'emoji',
        'description',
    ];

    public function levels(): HasMany
    {
        return $this->hasMany(CareerLevel::class)->orderBy('level_number');
    }

    public function modules(): HasManyThrough
    {
        return $this->hasManyThrough(Module::class, CareerLevel::class);
    }
}
