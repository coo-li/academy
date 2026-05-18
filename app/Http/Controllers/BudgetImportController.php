<?php

namespace App\Http\Controllers;

use App\Services\BudgetImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BudgetImportController extends Controller
{
    public function __construct(
        protected BudgetImportService $budgetImportService
    ) {}

    public function receiveWebhook(Request $request): JsonResponse
    {
        try {
            if (!$this->validateApiKey($request)) {
                Log::warning('Budget webhook: Invalid API key', ['ip' => $request->ip()]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Detect format: Legacy (data array) vs New (entries array)
            $isLegacyFormat = $request->has('data') && !$request->has('entries');
            
            Log::info('Budget webhook received', [
                'ip' => $request->ip(),
                'payload_size' => strlen($request->getContent()),
                'format' => $isLegacyFormat ? 'legacy' : 'standard',
                'year' => $request->input('year'),
            ]);

            if ($isLegacyFormat) {
                return $this->handleLegacyFormat($request);
            }

            return $this->handleStandardFormat($request);

        } catch (ValidationException $e) {
            Log::warning('Budget webhook validation failed', [
                'errors' => $e->errors(),
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'error' => 'Validation Failed',
                'messages' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Budget webhook error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            ], 500);
        }
    }

    protected function handleStandardFormat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.budget_tracker_id' => 'nullable|string',
            'entries.*.name' => 'nullable|string|max:255',
            'entries.*.label' => 'required|string|max:500',
            'entries.*.amount' => 'required|numeric|min:0',
            'entries.*.date' => 'required|date',
            'entries.*.cost_type' => 'nullable|in:monetary,time',
            'entries.*.budget_type' => 'nullable|in:used,available',
            'entries.*.sheet_origin' => 'nullable|string|max:255',
            'entries.*.project_id' => 'nullable|string|max:50',
        ]);

        foreach ($validated['entries'] as $index => $entry) {
            if (empty($entry['budget_tracker_id']) && empty($entry['name'])) {
                return response()->json([
                    'error' => 'Validation Failed',
                    'messages' => ["entries.{$index}" => ['Either budget_tracker_id or name is required']],
                ], 422);
            }
        }

        // Add sheet_origin to label if provided
        $validated['entries'] = array_map(function ($entry) {
            if (!empty($entry['sheet_origin']) && !str_starts_with($entry['label'], $entry['sheet_origin'])) {
                $entry['label'] = $entry['sheet_origin'] . ': ' . $entry['label'];
            }
            return $entry;
        }, $validated['entries']);

        $results = $this->budgetImportService->processWebhookData($validated);

        $statusCode = count($results['errors']) === 0 ? 200 : 207;

        Log::info('Budget webhook processed (standard format)', [
            'processed' => $results['processed'],
            'error_count' => count($results['errors']),
            'errors' => $results['errors'],
        ]);

        return response()->json([
            'success' => count($results['errors']) === 0,
            'processed' => $results['processed'],
            'errors' => $results['errors'],
        ], $statusCode);
    }

    protected function handleLegacyFormat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2020|max:2100',
            'data' => 'required|array|min:1',
            'data.*.label' => 'required|string|max:500',
            'data.*.name' => 'required|string|max:255',
            'data.*.soll_stunden' => 'nullable|numeric',
            'data.*.ist_stunden' => 'nullable|numeric',
            'data.*.monat' => 'nullable', // Can be string, number, or Date object from Google Sheets
            'data.*.sheet_origin' => 'nullable|string',
        ]);

        $year = $validated['year'] ?? date('Y');
        $entries = [];
        $skipped = 0;

        foreach ($validated['data'] as $item) {
            // Parse month to date (format: "01/2026" or similar)
            $date = $this->parseMonthToDate($item['monat'] ?? null, $year);
            
            if (!$date) {
                Log::warning('Legacy format: Could not parse month', ['monat' => $item['monat'] ?? null]);
                $date = date('Y-m-01'); // Fallback: first of current month
            }

            $istStunden = floatval($item['ist_stunden'] ?? 0);
            $sollStunden = floatval($item['soll_stunden'] ?? 0);
            
            // Skip entries where both are 0 or empty
            if ($istStunden == 0 && $sollStunden == 0) {
                $skipped++;
                continue;
            }

            // Build label with sheet origin if available
            $label = $item['label'];
            if (!empty($item['sheet_origin']) && !str_starts_with($label, $item['sheet_origin'])) {
                $label = $item['sheet_origin'] . ': ' . $label;
            }

            $baseEntry = [
                'name' => $item['name'],
                'label' => $label,
                'date' => $date,
                'cost_type' => 'monetary', // Data from Budget Tracker is in Euros
            ];

            // Create separate entries for Ist (used) and Soll (available)
            if ($istStunden > 0) {
                $entries[] = array_merge($baseEntry, [
                    'amount' => $istStunden,
                    'budget_type' => 'used',
                ]);
            }

            if ($sollStunden > 0) {
                $entries[] = array_merge($baseEntry, [
                    'amount' => $sollStunden,
                    'budget_type' => 'available',
                ]);
            }
        }

        if (empty($entries)) {
            return response()->json([
                'success' => true,
                'processed' => 0,
                'skipped' => $skipped,
                'message' => 'No entries with hours > 0 found',
                'errors' => [],
            ]);
        }

        Log::info('Legacy format converted', [
            'original_count' => count($validated['data']),
            'converted_count' => count($entries),
            'skipped' => $skipped,
        ]);

        $results = $this->budgetImportService->processWebhookData(['entries' => $entries]);

        $statusCode = count($results['errors']) === 0 ? 200 : 207;

        Log::info('Budget webhook processed (legacy format)', [
            'processed' => $results['processed'],
            'skipped' => $skipped,
            'error_count' => count($results['errors']),
            'errors' => $results['errors'],
        ]);

        return response()->json([
            'success' => count($results['errors']) === 0,
            'processed' => $results['processed'],
            'skipped' => $skipped,
            'errors' => $results['errors'],
        ], $statusCode);
    }

    protected function parseMonthToDate(mixed $monat, int $fallbackYear): ?string
    {
        if ($monat === null || $monat === '') {
            return null;
        }

        // If it's a number, it might be an Excel/Google Sheets date serial number
        if (is_numeric($monat)) {
            $serial = (int) $monat;
            
            // Excel serial date: days since 1899-12-30
            // Valid range check (1900-01-01 to 2100-12-31)
            if ($serial > 1 && $serial < 73415) {
                try {
                    $date = \Carbon\Carbon::createFromFormat('Y-m-d', '1899-12-30')
                        ->addDays($serial);
                    return $date->format('Y-m-01');
                } catch (\Exception $e) {
                    Log::warning('Could not parse serial date', ['monat' => $monat]);
                }
            }
            
            // It might just be a month number (1-12)
            if ($serial >= 1 && $serial <= 12) {
                $month = str_pad($serial, 2, '0', STR_PAD_LEFT);
                return "{$fallbackYear}-{$month}-01";
            }
            
            return null;
        }

        $monat = trim((string) $monat);

        // Format: "01/2026" or "1/2026"
        if (preg_match('/^(\d{1,2})\/(\d{4})$/', $monat, $matches)) {
            $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $year = $matches[2];
            return "{$year}-{$month}-01";
        }

        // Format: "2026-01" or "2026-01-15"
        if (preg_match('/^(\d{4})-(\d{2})/', $monat, $matches)) {
            return "{$matches[1]}-{$matches[2]}-01";
        }

        // Format: ISO date string from JavaScript Date object
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T/', $monat, $matches)) {
            return "{$matches[1]}-{$matches[2]}-01";
        }

        // Format: "Januar 2026" or "Jan 2026"
        $months = [
            'januar' => '01', 'jan' => '01',
            'februar' => '02', 'feb' => '02',
            'märz' => '03', 'mar' => '03', 'mrz' => '03',
            'april' => '04', 'apr' => '04',
            'mai' => '05', 'may' => '05',
            'juni' => '06', 'jun' => '06',
            'juli' => '07', 'jul' => '07',
            'august' => '08', 'aug' => '08',
            'september' => '09', 'sep' => '09',
            'oktober' => '10', 'okt' => '10', 'oct' => '10',
            'november' => '11', 'nov' => '11',
            'dezember' => '12', 'dez' => '12', 'dec' => '12',
        ];

        $lower = mb_strtolower($monat);
        foreach ($months as $name => $num) {
            if (str_contains($lower, $name)) {
                if (preg_match('/(\d{4})/', $monat, $yearMatch)) {
                    return "{$yearMatch[1]}-{$num}-01";
                }
                return "{$fallbackYear}-{$num}-01";
            }
        }

        return null;
    }

    public function parsePreview(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'label' => 'required|string|max:500',
            ]);

            $parsed = $this->budgetImportService->parseLabel($validated['label']);

            return response()->json([
                'label' => $validated['label'],
                'parsed' => $parsed,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Failed',
                'messages' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            ], 500);
        }
    }

    protected function validateApiKey(Request $request): bool
    {
        $providedKey = $request->header('X-Budget-Import-Key') 
            ?? $request->input('api_key');

        $expectedKey = config('services.budget_import.key');

        if (empty($expectedKey)) {
            Log::error('BUDGET_IMPORT_KEY not configured');
            return false;
        }

        return hash_equals($expectedKey, (string) $providedKey);
    }

    public function testConnection(Request $request): JsonResponse
    {
        $apiKeyValid = $this->validateApiKey($request);
        
        return response()->json([
            'status' => 'ok',
            'api_key_valid' => $apiKeyValid,
            'timestamp' => now()->toIso8601String(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]);
    }

    public function testImport(Request $request): JsonResponse
    {
        try {
            if (!$this->validateApiKey($request)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validated = $request->validate([
                'budget_tracker_id' => 'nullable|string',
                'name' => 'nullable|string|max:255',
                'label' => 'required|string|max:500',
                'amount' => 'required|numeric|min:0',
                'date' => 'required|date',
                'cost_type' => 'nullable|in:monetary,time',
            ]);

            if (empty($validated['budget_tracker_id']) && empty($validated['name'])) {
                return response()->json([
                    'error' => 'Either budget_tracker_id or name is required',
                ], 422);
            }

            $user = $this->budgetImportService->findUser($validated);

            $parsedLabel = $this->budgetImportService->parseLabel($validated['label']);

            return response()->json([
                'status' => 'dry_run',
                'user_found' => $user !== null,
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ] : null,
                'parsed_label' => $parsedLabel,
                'entry_data' => [
                    'label' => $validated['label'],
                    'amount' => $validated['amount'],
                    'date' => $validated['date'],
                    'cost_type' => $validated['cost_type'] ?? 'monetary',
                ],
                'message' => $user 
                    ? 'Entry would be created successfully (dry run - no data saved)'
                    : 'User not found - entry would fail',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Failed',
                'messages' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error',
                'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            ], 500);
        }
    }

    public function lookupUser(Request $request): JsonResponse
    {
        try {
            if (!$this->validateApiKey($request)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $name = $request->query('name');
            $budgetTrackerId = $request->query('budget_tracker_id');

            if (empty($name) && empty($budgetTrackerId)) {
                return response()->json([
                    'error' => 'Either name or budget_tracker_id query parameter is required',
                ], 422);
            }

            $user = $this->budgetImportService->findUser([
                'name' => $name,
                'budget_tracker_id' => $budgetTrackerId,
            ]);

            if (!$user) {
                return response()->json([
                    'found' => false,
                    'search' => [
                        'name' => $name,
                        'budget_tracker_id' => $budgetTrackerId,
                    ],
                    'message' => 'No user found with these criteria',
                ], 404);
            }

            return response()->json([
                'found' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'budget_tracker_id' => $user->budget_tracker_id,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error',
                'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            ], 500);
        }
    }
}
