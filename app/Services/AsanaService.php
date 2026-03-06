<?php

namespace App\Services;

use App\Models\Module;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsanaService
{
    protected string $baseUrl = 'https://app.asana.com/api/1.0';
    protected ?string $token;
    protected ?string $projectId;

    public function __construct()
    {
        $this->token = config('services.asana.token');
        $this->projectId = config('services.asana.project_id');
    }

    public function isConfigured(): bool
    {
        return filled($this->token) && filled($this->projectId);
    }

    /**
     * Create an Asana task for a course booking with full context.
     */
    public function createBookingTask(User $user, Module $module, ?TrainingSession $session = null): ?array
    {
        $profileUrl = url("/profile/{$user->id}");
        $workshopDate = $session?->start_at?->format('d.m.Y, H:i') ?? 'Kein Termin';
        $location = $session?->location ?? '–';
        $bookingDate = now()->format('d.m.Y, H:i');

        $name = "Akademie-Buchung: {$module->title} - {$user->name}";

        $notes = implode("\n", [
            "=== Akademie Kursbuchung ===",
            "",
            "Mitarbeiter: {$user->name}",
            "E-Mail: {$user->email}",
            "Buchungsdatum: {$bookingDate}",
            "",
            "Modul: {$module->title}",
            "Methode: {$module->methodLabel()}",
            "Session-Datum: {$workshopDate}",
            "Ort: {$location}",
            "",
            "Profil-Link: {$profileUrl}",
            "",
            "Diese Task wurde automatisch von der td Academy erstellt.",
        ]);

        $dueDate = $session?->start_at?->format('Y-m-d');

        return $this->createTask($name, $notes, dueOn: $dueDate);
    }

    /**
     * Create a generic task in the configured Asana project.
     */
    public function createTask(string $name, string $notes = '', ?string $assignee = null, ?string $dueOn = null): ?array
    {
        if (! $this->token) {
            Log::warning('Asana: No API token configured, skipping task creation.', ['task' => $name]);
            return null;
        }

        try {
            $data = [
                'name' => $name,
                'notes' => $notes,
                'projects' => array_filter([$this->projectId]),
            ];

            if ($assignee) {
                $data['assignee'] = $assignee;
            }

            if ($dueOn) {
                $data['due_on'] = $dueOn;
            }

            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/tasks", ['data' => $data]);

            if ($response->successful()) {
                Log::info('Asana: Task created.', ['gid' => $response->json('data.gid')]);
                return $response->json('data');
            }

            Log::error('Asana: Task creation failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Asana: Exception during task creation.', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Delete (or mark complete) an Asana task by its GID.
     * Returns true on success, false otherwise.
     */
    public function deleteTask(string $taskGid): bool
    {
        if (! $this->token) {
            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->delete("{$this->baseUrl}/tasks/{$taskGid}");

            if ($response->successful()) {
                Log::info('Asana: Task deleted.', ['gid' => $taskGid]);
                return true;
            }

            Log::warning('Asana: Task deletion failed, attempting to mark completed.', [
                'gid' => $taskGid,
                'status' => $response->status(),
            ]);

            return $this->completeTask($taskGid);
        } catch (\Throwable $e) {
            Log::error('Asana: Exception during task deletion.', [
                'gid' => $taskGid,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Mark an Asana task as completed.
     */
    public function completeTask(string $taskGid): bool
    {
        if (! $this->token) {
            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->put("{$this->baseUrl}/tasks/{$taskGid}", [
                    'data' => ['completed' => true],
                ]);

            if ($response->successful()) {
                Log::info('Asana: Task marked as completed.', ['gid' => $taskGid]);
                return true;
            }
        } catch (\Throwable $e) {
            Log::error('Asana: Exception completing task.', [
                'gid' => $taskGid,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Add a comment to an existing Asana task (used for cancellation notes).
     */
    public function addComment(string $taskGid, string $text): bool
    {
        if (! $this->token) {
            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/tasks/{$taskGid}/stories", [
                    'data' => ['text' => $text],
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Asana: Exception adding comment.', ['error' => $e->getMessage()]);
        }

        return false;
    }
}
