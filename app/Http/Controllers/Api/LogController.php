<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function __construct(
        private AuditLogService $logs,
    ) {}

    public function registrar(Request $request): JsonResponse
    {
        $request->validate([
            'patient_no' => ['required'],
            'section' => ['required', 'string'],
        ]);

        try {
            $logs = $this->logs->registrarLogs((int) $request->input('patient_no'), $request->input('section'));
        } catch (\InvalidArgumentException) {
            return response()->json([]);
        }

        return response()->json($logs);
    }

    public function doctors(Request $request): JsonResponse
    {
        $request->validate([
            'patient_no' => ['required'],
            'section' => ['required', 'string'],
        ]);

        try {
            $logs = $this->logs->doctorLogs((int) $request->input('patient_no'), $request->input('section'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json($logs);
    }
}
