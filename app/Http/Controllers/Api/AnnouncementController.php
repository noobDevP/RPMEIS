<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Services\AnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(
        private AnnouncementService $announcements,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'message' => $this->announcements->current()?->message ?? '',
        ]);
    }

    public function update(UpdateAnnouncementRequest $request): JsonResponse
    {
        $this->announcements->updateMessage($request->input('message'));

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement updated successfully',
        ]);
    }

    public function updateLegacyForm(Request $request): RedirectResponse
    {
        $request->validate(['announcement' => ['required', 'string']]);

        $this->announcements->updateMessage($request->input('announcement'));

        return redirect('/registrar.php?success=1');
    }
}
