<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetSyncProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'project_name',
        'customer_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope für aktive Projekte.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für inaktive Projekte.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Gibt alle aktiven Projekt-IDs zurück.
     */
    public static function getActiveProjectIds(): array
    {
        return static::active()->pluck('project_id')->toArray();
    }

    /**
     * Prüft ob eine Projekt-ID aktiv ist.
     */
    public static function isProjectActive(string $projectId): bool
    {
        return static::active()->where('project_id', $projectId)->exists();
    }

    /**
     * Erstellt oder aktualisiert ein Projekt.
     */
    public static function syncFromApi(array $projectData): self
    {
        return static::updateOrCreate(
            ['project_id' => $projectData['project_id']],
            [
                'project_name' => $projectData['project_name'] ?? 'Unbekanntes Projekt',
                'customer_name' => $projectData['customer_name'] ?? null,
            ]
        );
    }
}
