<?php

namespace App\Services;

use App\Models\DoctorLog;
use App\Models\RegistrarLog;
use App\Support\StationRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AuditLogService
{
    public function registrarLogs(int $patientNo, string $section): Collection
    {
        $station = $this->stationForSection($section);
        $approvalColumn = $station['log_approval'];
        $timestampColumn = $station['log_timestamp'];

        return RegistrarLog::query()
            ->select([
                DB::raw("{$approvalColumn} as approval"),
                DB::raw("{$timestampColumn} as timestamp"),
                DB::raw("COALESCE(user_username, 'None') as user_username"),
            ])
            ->where('patient_no_logs', (string) $patientNo)
            ->whereNotNull($approvalColumn)
            ->whereNotNull($timestampColumn)
            ->orderByDesc($timestampColumn)
            ->get();
    }

    public function doctorLogs(int $patientNo, string $section): Collection
    {
        $station = $this->stationForSection($section);
        $approvalColumn = $station['log_approval'];
        $timestampColumn = $station['log_timestamp'];

        return DoctorLog::query()
            ->select([
                DB::raw("{$approvalColumn} as approval"),
                DB::raw("{$timestampColumn} as timestamp"),
                'user_username',
            ])
            ->where('patient_no_logs', $patientNo)
            ->orderByDesc($timestampColumn)
            ->get();
    }

    private function stationForSection(string $section): array
    {
        foreach (StationRegistry::keys() as $stationKey) {
            $station = StationRegistry::get($stationKey);

            if (($station['log_section'] ?? $stationKey) === $section) {
                return $station;
            }
        }

        throw new InvalidArgumentException("Invalid log section [{$section}].");
    }
}
