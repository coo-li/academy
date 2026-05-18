<?php

namespace App\Http\Controllers;

use App\Models\TrainingBooking;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AsanaWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // Asana Webhook Handshake (bei Registrierung)
        if ($request->hasHeader('X-Hook-Secret')) {
            return response('', 200)
                ->header('X-Hook-Secret', $request->header('X-Hook-Secret'));
        }

        // Signatur-Validierung (optional, aber empfohlen)
        $signature = $request->header('X-Hook-Signature');
        if ($signature && !$this->verifySignature($request, $signature)) {
            Log::warning('Asana Webhook: Invalid signature');
            return response('Invalid signature', 401);
        }

        $events = $request->input('events', []);

        foreach ($events as $event) {
            $this->processEvent($event);
        }

        return response('', 200);
    }

    protected function processEvent(array $event): void
    {
        $resource = $event['resource'] ?? [];
        $action = $event['action'] ?? '';
        $change = $event['change'] ?? [];

        // Wir interessieren uns nur für Task-Änderungen
        if (($resource['resource_type'] ?? '') !== 'task') {
            return;
        }

        $taskGid = $resource['gid'] ?? null;
        if (!$taskGid) {
            return;
        }

        // Task wurde als erledigt markiert
        if ($action === 'changed' && ($change['field'] ?? '') === 'completed') {
            $isCompleted = $change['new_value'] ?? false;
            
            if ($isCompleted) {
                $this->markBookingComplete($taskGid);
            }
        }
    }

    protected function markBookingComplete(string $taskGid): void
    {
        $booking = TrainingBooking::where('asana_task_gid', $taskGid)->first();

        if (!$booking) {
            Log::info('Asana Webhook: No booking found for task', ['gid' => $taskGid]);
            return;
        }

        if ($booking->budget_entry_created) {
            Log::info('Asana Webhook: Booking already completed', ['booking_id' => $booking->id]);
            return;
        }

        $booking->update(['budget_entry_created' => true]);

        Log::info('Asana Webhook: Booking marked as complete', [
            'booking_id' => $booking->id,
            'task_gid' => $taskGid,
        ]);
    }

    protected function verifySignature(Request $request, string $signature): bool
    {
        $secret = config('services.asana.webhook_secret');
        
        if (!$secret) {
            return true;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
