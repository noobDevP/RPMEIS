<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analytics,
    ) {}

    public function categoryCounts(Request $request): JsonResponse
    {
        if (session('staff.role') === 'EXECUTIVE') {
            \App\Models\ExecutiveLog::query()->create([
                'action' => 'Viewed Analytics (' . $request->query('category', '') . ' - Batch: ' . $request->query('batch', 'All') . ')',
                'user_username' => session('staff.display_name')
            ]);
        }

        return response()->json(
            $this->analytics->categoryCounts(
                $request->query('batch', 'All'),
                (string) $request->query('category', ''),
            )
        );
    }

    public function chartData(Request $request): JsonResponse
    {
        $batch = $request->query('batch', 'All');
        $categories = config('stations.analytics_categories', []);
        
        $stationData = [];
        foreach ($categories as $category => $column) {
            $counts = $this->analytics->categoryCounts($batch, $category);
            
            $stationData[$category] = [
                'GO' => $counts['goCount'],
                'NO GO' => $counts['noGoCount'],
                'COMPLIANCE' => $counts['complyingCount'],
                'WAITING FOR RESULT' => $counts['waitingCount'],
            ];
        }

        $overallData = $this->analytics->overallCounts($batch);

        return response()->json([
            'station' => $stationData,
            'overall' => $overallData
        ]);
    }

    public function batches(): JsonResponse
    {
        return response()->json($this->analytics->distinctBatches());
    }
}
