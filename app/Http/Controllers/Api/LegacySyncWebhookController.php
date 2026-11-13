<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyPatientSyncService;
use App\Support\LegacySignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LegacySyncWebhookController extends Controller
{
    public function __construct(
        private LegacySignatureVerifier $verifier,
        private LegacyPatientSyncService $syncService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('services.philarmylab_legacy.secret');
        if ($secret === '') {
            return response()->json(['status' => 'error', 'message' => 'Legacy sync secret not configured.'], 503);
        }

        $rawBody = $request->getContent();
        $timestampHeader = $request->header('X-Legacy-Timestamp');
        $signatureHeader = $request->header('X-Legacy-Signature');
        $tolerance = (int) config('services.philarmylab_legacy.webhook_tolerance_seconds', 300);

        if (! $this->verifier->verify($rawBody, $timestampHeader, $signatureHeader, $secret, $tolerance)) {
            Log::warning('Rejected unauthorized legacy webhook request', [
                'ip' => $request->ip(),
                'has_timestamp' => $timestampHeader !== null,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Invalid webhook signature.'], 401);
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid JSON payload.'], 422);
        }

        $event = (string) ($payload['event'] ?? '');

        try {
            if ($event === 'patient.upserted' && is_array($payload['patient'] ?? null)) {
                $result = $this->syncService->syncPatientRow($payload['patient'], dryRun: false, fetchRemoteFiles: true);

                return response()->json([
                    'status' => 'ok',
                    'event' => $event,
                    'patient_id' => $result['patient_id'],
                    'created' => $result['created'],
                    'warnings' => $result['warnings'],
                ]);
            }

            if ($event === 'patients.batch' && is_array($payload['patients'] ?? null)) {
                $synced = [];
                foreach ($payload['patients'] as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $res = $this->syncService->syncPatientRow($row, dryRun: false, fetchRemoteFiles: true);
                    $synced[] = $res['patient_id'];
                }

                return response()->json([
                    'status' => 'ok',
                    'event' => $event,
                    'count' => count($synced),
                    'patient_ids' => $synced,
                ]);
            }

            if ($event === 'patient.deleted' && isset($payload['patient_no'])) {
                $patientNo = (int) $payload['patient_no'];
                $deleted = $this->syncService->deleteLegacyPatient($patientNo);

                return response()->json([
                    'status' => 'ok',
                    'event' => $event,
                    'patient_id' => $patientNo,
                    'deleted' => $deleted,
                ]);
            }

            return response()->json(['status' => 'error', 'message' => 'Unsupported or incomplete webhook event.'], 422);
        } catch (Throwable $e) {
            Log::error('Legacy sync webhook processing failed', [
                'event' => $event,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Sync processing failed.'], 500);
        }
    }
}
