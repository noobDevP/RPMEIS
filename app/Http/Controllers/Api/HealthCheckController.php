<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckController extends Controller
{
    /**
     * Comprehensive production health check endpoint.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => 'ok',
            'queue' => 'ok',
            'cache' => 'ok',
            'storage' => 'ok',
        ];
        $errors = [];

        // 1. Database Check
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $checks['database'] = 'failed';
            $errors['database'] = $e->getMessage();
        }

        // 2. Queue Check (Database queue driver)
        try {
            if (config('queue.default') === 'database') {
                DB::table('jobs')->limit(1)->count();
            }
        } catch (Throwable $e) {
            $checks['queue'] = 'failed';
            $errors['queue'] = $e->getMessage();
        }

        // 3. Cache Check
        try {
            $cacheKey = '_health_check_' . hrtime(true);
            Cache::put($cacheKey, 1, 10);
            if (Cache::get($cacheKey) !== 1) {
                throw new \Exception('Cache read/write mismatch');
            }
            Cache::forget($cacheKey);
        } catch (Throwable $e) {
            $checks['cache'] = 'failed';
            $errors['cache'] = $e->getMessage();
        }

        // 4. Storage Writability
        try {
            $storagePath = storage_path('framework/cache');
            if (!is_writable($storagePath)) {
                throw new \Exception("Storage path [{$storagePath}] is not writable.");
            }
        } catch (Throwable $e) {
            $checks['storage'] = 'failed';
            $errors['storage'] = $e->getMessage();
        }

        $isHealthy = empty($errors);

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'service' => 'philarmylab-api',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'errors' => $errors ?: null,
        ], $isHealthy ? 200 : 503);
    }
}
