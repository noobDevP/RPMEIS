<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBase64PhotoRequest;
use App\Models\Patient;
use App\Services\PhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function __construct(
        private PhotoService $photos,
    ) {}

    public function storeBase64(StoreBase64PhotoRequest $request): JsonResponse
    {
        try {
            $path = $this->photos->storeBase64Image($request->input('imageData'));

            return response()->json([
                'status' => 'success',
                'filePath' => $path,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function reupload(Request $request, int $patientNo): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $patient = Patient::query()->find($patientNo);

        if (! $patient) {
            return response()->json(['success' => false, 'error' => 'Patient not found'], 404);
        }

        try {
            $path = $this->photos->reupload($patient, $request->file('photo'));

            return response()->json(['success' => true, 'new_path' => $path]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
