<?php

namespace App\Services;

use App\Models\EmployeeBudgetRule;
use App\Models\User;
use App\Models\UserBudget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeBudgetCategoryService
{
    /**
     * Determine which BASE budget rule applies to a user.
     * Rules are evaluated by priority (highest first).
     * Manual override (employee_budget_rule_id) takes precedence if it's a base rule.
     */
    public function determineRuleForUser(User $user): ?EmployeeBudgetRule
    {
        return $this->determineBaseRuleForUser($user);
    }

    /**
     * Determine which BASE budget rule applies to a user.
     * Base rules define the total budget and hourly rate.
     */
    public function determineBaseRuleForUser(User $user): ?EmployeeBudgetRule
    {
        if ($user->employee_budget_rule_id) {
            $manual = EmployeeBudgetRule::find($user->employee_budget_rule_id);
            if ($manual && $manual->is_active && $manual->isBase()) {
                return $manual;
            }
        }

        $rules = EmployeeBudgetRule::active()
            ->base()
            ->where('is_default', false)
            ->byPriority()
            ->get();

        foreach ($rules as $rule) {
            if ($rule->matchesUser($user)) {
                return $rule;
            }
        }

        return EmployeeBudgetRule::getDefault();
    }

    /**
     * Find all matching OVERLAY rules for a user.
     * Overlay rules define cash limits and other restrictions.
     */
    public function findMatchingOverlayRules(User $user): Collection
    {
        $rules = EmployeeBudgetRule::active()
            ->overlay()
            ->byPriority()
            ->get();

        return $rules->filter(fn (EmployeeBudgetRule $rule) => $rule->matchesUser($user));
    }

    /**
     * Calculate the effective cash limit from overlay rules.
     * Returns null if no overlay applies (no cash limit).
     * If multiple overlays apply, the lowest cash limit wins.
     */
    public function calculateCashLimit(Collection $overlayRules, ?EmployeeBudgetRule $baseRule): ?float
    {
        if ($overlayRules->isEmpty()) {
            return null;
        }

        $cashLimits = $overlayRules
            ->filter(fn ($rule) => $rule->max_cash_budget !== null)
            ->pluck('max_cash_budget')
            ->map(fn ($v) => (float) $v);

        if ($cashLimits->isEmpty()) {
            return null;
        }

        return $cashLimits->min();
    }

    /**
     * Calculate the effective budget for a user for a given year.
     * Combines base rule (total budget) with overlay rules (cash limits).
     */
    public function calculateEffectiveBudget(User $user, int $year): array
    {
        $baseRule = $this->determineBaseRuleForUser($user);
        $overlayRules = $this->findMatchingOverlayRules($user);
        
        $maxBudget = $baseRule?->max_money_budget ?? 3000.00;
        $hourlyRate = $baseRule?->getEffectiveHourlyRate($user) ?? $user->getHourlyRate();
        $maxCash = $this->calculateCashLimit($overlayRules, $baseRule);
        
        $userBudget = $user->getBudgetForYear($year);
        $spent = $user->getSpentBudgetForYear($year, true);
        $remaining = $maxBudget - $spent;

        return [
            'rule' => $baseRule,
            'base_rule' => $baseRule,
            'overlay_rules' => $overlayRules,
            'rule_name' => $baseRule?->name ?? 'Standard (Fallback)',
            'rule_slug' => $baseRule?->slug ?? 'fallback',
            'max_budget' => (float) $maxBudget,
            'max_cash' => $maxCash,
            'has_cash_limit' => $maxCash !== null,
            'spent' => (float) $spent,
            'remaining' => (float) $remaining,
            'is_over_budget' => $remaining < 0,
            'hourly_rate' => (float) $hourlyRate,
            'is_manual_override' => $user->employee_budget_rule_id !== null,
        ];
    }

    /**
     * Get the budget allowance for a user (for use in other services).
     */
    public function getBudgetAllowance(User $user): float
    {
        $rule = $this->determineBaseRuleForUser($user);
        return (float) ($rule?->max_money_budget ?? 3000.00);
    }

    /**
     * Get the max cash budget for a user (if any overlay applies).
     */
    public function getMaxCashBudget(User $user): ?float
    {
        $overlayRules = $this->findMatchingOverlayRules($user);
        $baseRule = $this->determineBaseRuleForUser($user);
        return $this->calculateCashLimit($overlayRules, $baseRule);
    }

    /**
     * Get the effective hourly rate for a user.
     */
    public function getEffectiveHourlyRate(User $user): float
    {
        $rule = $this->determineBaseRuleForUser($user);
        return (float) ($rule?->getEffectiveHourlyRate($user) ?? $user->getHourlyRate());
    }

    /**
     * Sync all UserBudget records for a given year based on rules.
     */
    public function syncAllUserBudgets(int $year): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

        $users = User::active()
            ->whereNotNull('personio_id')
            ->get();

        DB::beginTransaction();

        try {
            foreach ($users as $user) {
                $rule = $this->determineRuleForUser($user);
                $maxBudget = $rule?->max_money_budget ?? 3000.00;

                $userBudget = UserBudget::where('user_id', $user->id)
                    ->where('year', $year)
                    ->first();

                if (!$userBudget) {
                    UserBudget::create([
                        'user_id' => $user->id,
                        'year' => $year,
                        'total_allowance_monetary' => $maxBudget,
                    ]);
                    $stats['created']++;
                } elseif ((float) $userBudget->total_allowance_monetary !== (float) $maxBudget) {
                    $userBudget->update(['total_allowance_monetary' => $maxBudget]);
                    $stats['updated']++;
                } else {
                    $stats['unchanged']++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('EmployeeBudgetCategoryService: Sync failed', [
                'error' => $e->getMessage(),
                'year' => $year,
            ]);
            throw $e;
        }

        return $stats;
    }

    /**
     * Get all users grouped by their applicable rule.
     */
    public function getUsersByRule(): Collection
    {
        $users = User::active()
            ->whereNotNull('personio_id')
            ->with('team')
            ->get();

        return $users->groupBy(function (User $user) {
            $rule = $this->determineRuleForUser($user);
            return $rule?->slug ?? 'fallback';
        });
    }

    /**
     * Preview which rule would apply to each user (for admin UI).
     * Shows both base rule and any matching overlay rules.
     */
    public function previewRuleAssignments(): Collection
    {
        $users = User::active()
            ->whereNotNull('personio_id')
            ->with('team')
            ->orderBy('name')
            ->get();

        return $users->map(function (User $user) {
            $baseRule = $this->determineBaseRuleForUser($user);
            $overlayRules = $this->findMatchingOverlayRules($user);
            $maxCash = $this->calculateCashLimit($overlayRules, $baseRule);
            
            return [
                'user' => $user,
                'rule' => $baseRule,
                'base_rule' => $baseRule,
                'overlay_rules' => $overlayRules,
                'rule_name' => $baseRule?->name ?? 'Standard (Fallback)',
                'max_budget' => $baseRule?->max_money_budget ?? 3000.00,
                'max_cash' => $maxCash,
                'has_cash_limit' => $maxCash !== null,
                'is_manual' => $user->employee_budget_rule_id !== null,
                'match_reason' => $this->getMatchReason($user, $baseRule),
                'overlay_reason' => $this->getOverlayReason($overlayRules),
            ];
        });
    }

    /**
     * Get a human-readable reason for matching overlay rules.
     */
    protected function getOverlayReason(Collection $overlayRules): ?string
    {
        if ($overlayRules->isEmpty()) {
            return null;
        }

        return $overlayRules->pluck('name')->implode(', ');
    }

    /**
     * Get a human-readable reason why a rule matches a user.
     */
    protected function getMatchReason(User $user, ?EmployeeBudgetRule $rule): string
    {
        if (!$rule) {
            return 'Keine Regel konfiguriert';
        }

        if ($user->employee_budget_rule_id === $rule->id) {
            return 'Manuell zugewiesen';
        }

        if ($rule->is_default) {
            return 'Standard-Regel';
        }

        if (!empty($rule->applies_to_departments) && $rule->matchesUser($user)) {
            return 'Department: ' . $user->personio_department;
        }

        if (!empty($rule->applies_to_positions) && $rule->matchesUser($user)) {
            return 'Position: ' . $user->personio_position;
        }

        if (!empty($rule->applies_to_levels) && $rule->matchesUser($user)) {
            return 'Karrierestufe: ' . $user->personio_level_raw;
        }

        if ($rule->working_hours_min !== null || $rule->working_hours_max !== null) {
            return 'Wochenstunden: ' . ($user->weekly_working_hours ?? 'n/a') . 'h';
        }

        return 'Regelkriterien erfüllt';
    }

    /**
     * Get statistics about rule distribution (base rules only).
     */
    public function getRuleStatistics(): array
    {
        $usersByRule = $this->getUsersByRule();
        $rules = EmployeeBudgetRule::active()->base()->byPriority()->get();

        $stats = [];
        foreach ($rules as $rule) {
            $users = $usersByRule->get($rule->slug, collect());
            $stats[] = [
                'rule' => $rule,
                'user_count' => $users->count(),
                'total_budget' => $users->count() * (float) ($rule->max_money_budget ?? 0),
            ];
        }

        $fallbackUsers = $usersByRule->get('fallback', collect());
        if ($fallbackUsers->isNotEmpty()) {
            $stats[] = [
                'rule' => null,
                'rule_name' => 'Fallback (keine Regel)',
                'user_count' => $fallbackUsers->count(),
                'total_budget' => $fallbackUsers->count() * 3000.00,
            ];
        }

        return $stats;
    }

    /**
     * Get statistics about overlay rule distribution.
     */
    public function getOverlayStatistics(): array
    {
        $users = User::active()
            ->whereNotNull('personio_id')
            ->get();

        $overlayRules = EmployeeBudgetRule::active()->overlay()->byPriority()->get();

        $stats = [];
        foreach ($overlayRules as $rule) {
            $matchingUsers = $users->filter(fn (User $user) => $rule->matchesUser($user));
            $stats[] = [
                'rule' => $rule,
                'user_count' => $matchingUsers->count(),
                'cash_limit' => (float) ($rule->max_cash_budget ?? 0),
            ];
        }

        return $stats;
    }
}
