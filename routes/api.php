<?php

use App\Http\Controllers\AsanaWebhookController;
use App\Http\Controllers\BudgetImportController;
use Illuminate\Support\Facades\Route;

// Debug-Route zum Testen
Route::get('/debug-test', function () {
    return response()->json(['status' => 'api is working']);
});

// Budget Import Webhook
Route::post('/budget/webhook', [BudgetImportController::class, 'receiveWebhook'])
    ->name('budget.webhook');

Route::post('/budget/parse-preview', [BudgetImportController::class, 'parsePreview'])
    ->name('budget.parse-preview');

// Budget Import Test-Endpoints
Route::get('/budget/test', [BudgetImportController::class, 'testConnection'])
    ->name('budget.test');

Route::post('/budget/test-import', [BudgetImportController::class, 'testImport'])
    ->name('budget.test-import');

Route::get('/budget/lookup-user', [BudgetImportController::class, 'lookupUser'])
    ->name('budget.lookup-user');

// Asana Webhook für Task-Completion
Route::post('/webhooks/asana', [AsanaWebhookController::class, 'handle'])
    ->name('webhooks.asana');
