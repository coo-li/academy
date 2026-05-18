<?php

namespace App\Services;

use App\Models\BudgetEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BudgetImportService
{
    public const PREFIX_PE = 'PE ';
    public const PREFIX_TEAM_GOAL = 'Teamziel:';
    public const PREFIX_PERSONAL_GOAL = 'Persönliches Ziel:';
    public const PREFIX_INTERNAL_TRAINING = 'Interne Schulung:';

    public function processWebhookData(array $data): array
    {
        $results = [
            'processed' => 0,
            'errors' => [],
            'entries' => [],
        ];

        if (!isset($data['entries']) || !is_array($data['entries'])) {
            $results['errors'][] = 'Invalid data format: "entries" array expected';
            return $results;
        }

        DB::beginTransaction();

        try {
            foreach ($data['entries'] as $index => $entry) {
                $result = $this->processEntry($entry, $index);
                
                if ($result['success']) {
                    $results['processed']++;
                    $results['entries'][] = $result['entry'];
                } else {
                    $results['errors'][] = $result['error'];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Budget import failed', ['error' => $e->getMessage()]);
            $results['errors'][] = 'Transaction failed: ' . $e->getMessage();
        }

        return $results;
    }

    protected function processEntry(array $entry, int $index): array
    {
        $requiredFields = ['label', 'amount', 'date'];
        
        foreach ($requiredFields as $field) {
            if (!isset($entry[$field])) {
                return [
                    'success' => false,
                    'error' => "Entry {$index}: Missing required field '{$field}'",
                ];
            }
        }

        if (empty($entry['budget_tracker_id']) && empty($entry['name'])) {
            return [
                'success' => false,
                'error' => "Entry {$index}: Either 'budget_tracker_id' or 'name' is required",
            ];
        }

        $user = $this->findUser($entry);
        
        if (!$user) {
            $identifier = $entry['budget_tracker_id'] ?? $entry['name'] ?? 'unknown';
            return [
                'success' => false,
                'error' => "Entry {$index}: User '{$identifier}' not found",
            ];
        }

        $parsed = $this->parseLabel($entry['label']);
        $costType = $entry['cost_type'] ?? BudgetEntry::COST_TYPE_MONETARY;
        $budgetType = $entry['budget_type'] ?? BudgetEntry::BUDGET_TYPE_USED;
        
        // Normalize budget_type from Google Sheet format
        if ($budgetType === 'budget_used') {
            $budgetType = BudgetEntry::BUDGET_TYPE_USED;
        } elseif ($budgetType === 'budget_available') {
            $budgetType = BudgetEntry::BUDGET_TYPE_AVAILABLE;
        }

        $date = Carbon::parse($entry['date']);
        $projectId = $entry['project_id'] ?? null;

        // Use updateOrCreate to avoid duplicates
        $budgetEntry = BudgetEntry::updateOrCreate(
            [
                'user_id' => $user->id,
                'label' => $entry['label'],
                'budget_type' => $budgetType,
                'month' => $date->month,
                'year' => $date->year,
            ],
            [
                'project_id' => $projectId,
                'category' => $parsed['category'],
                'budget_name' => $parsed['budget_name'],
                'type' => $parsed['type'],
                'cost_type' => $costType,
                'amount' => (float) $entry['amount'],
                'date' => $date,
                'is_deductible_from_allowance' => $parsed['is_deductible'],
            ]
        );

        return [
            'success' => true,
            'entry' => $budgetEntry,
        ];
    }

    public function parseLabel(string $label): array
    {
        $originalLabel = trim($label);
        $label = $originalLabel;
        $category = null;
        $budgetName = $label;
        $type = BudgetEntry::TYPE_OTHER_INTERNAL;
        $isDeductible = false;

        // Remove "PE- " or "PE " prefix
        if (preg_match('/^PE[-\s]+/', $label)) {
            $label = preg_replace('/^PE[-\s]+/', '', $label);
        }

        // Split by colon to get category and budget name
        // Format: "Category: Budget Name" or "Category - Subcategory: Budget Name"
        if (str_contains($label, ':')) {
            $parts = explode(':', $label, 2);
            $category = trim($parts[0]);
            $budgetName = isset($parts[1]) ? trim($parts[1]) : $category;
        } else {
            $category = $label;
            $budgetName = $label;
        }

        // Determine type based on FULL LABEL (not just category)
        // This ensures "Teamziele SEA: Persönliches Ziel: X" is correctly identified as personal_goal
        $labelLower = mb_strtolower($originalLabel);
        
        // Check for personal goals first (higher priority)
        if (str_contains($labelLower, 'persönlich') || str_contains($labelLower, 'personal')) {
            $type = BudgetEntry::TYPE_PERSONAL_GOAL;
            $isDeductible = true;
        } elseif (str_contains($labelLower, 'interne schulung') || str_contains($labelLower, 'internal training')) {
            $type = BudgetEntry::TYPE_INTERNAL_TRAINING;
        } elseif (str_contains($labelLower, 'teamziel')) {
            $type = BudgetEntry::TYPE_TEAM_GOAL;
        } elseif (str_contains($labelLower, 'schulung') || str_contains($labelLower, 'training')) {
            $type = BudgetEntry::TYPE_INTERNAL_TRAINING;
        }

        return [
            'type' => $type,
            'is_deductible' => $isDeductible,
            'category' => $category,
            'budget_name' => $budgetName,
        ];
    }

    public function findUserByTrackerId(string $trackerId): ?User
    {
        return User::where('budget_tracker_id', $trackerId)->first();
    }

    public function findUserByName(string $name): ?User
    {
        $normalizedName = mb_strtolower(trim($name));
        
        return User::whereRaw('LOWER(name) = ?', [$normalizedName])->first();
    }

    public function findUser(array $entry): ?User
    {
        if (!empty($entry['budget_tracker_id'])) {
            $user = $this->findUserByTrackerId($entry['budget_tracker_id']);
            if ($user) {
                return $user;
            }
        }

        if (!empty($entry['name'])) {
            return $this->findUserByName($entry['name']);
        }

        return null;
    }

    public function createOrUpdateEntry(User $user, array $data): BudgetEntry
    {
        $parsed = $this->parseLabel($data['label']);

        return BudgetEntry::updateOrCreate(
            [
                'user_id' => $user->id,
                'label' => $data['label'],
                'date' => Carbon::parse($data['date']),
            ],
            [
                'type' => $parsed['type'],
                'cost_type' => $data['cost_type'] ?? BudgetEntry::COST_TYPE_MONETARY,
                'amount' => (float) $data['amount'],
                'is_deductible_from_allowance' => $parsed['is_deductible'],
            ]
        );
    }
}
