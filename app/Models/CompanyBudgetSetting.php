<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyBudgetSetting extends Model
{
    protected $fillable = [
        'year',
        'revenue_target',
        'service_dev_percentage',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'revenue_target' => 'decimal:2',
            'service_dev_percentage' => 'decimal:2',
        ];
    }

    public function getServiceDevBudgetAttribute(): float
    {
        return $this->revenue_target * ($this->service_dev_percentage / 100);
    }

    public static function forYear(int $year): ?self
    {
        return static::where('year', $year)->first();
    }
}
