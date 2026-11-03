<?php

namespace App\Services;

use App\Jobs\SyncFinalResultJob;
use App\Jobs\SyncStationResultJob;
use App\Models\Patient;

class PmeIntegrationService
{
    public function pushStationResult(Patient $patient, string $station, array $data): void
    {
        $appcode = $patient->appcode;
        if (! $appcode) {
            return;
        }

        SyncStationResultJob::dispatch((string) $appcode, $station, $data);
    }

    public function pushFinalResult(Patient $patient): void
    {
        $appcode = $patient->appcode;
        if (! $appcode) {
            return;
        }

        $chiefApproval = (string) ($patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? '');

        $stationApprovals = [
            'dental' => (string) ($patient->approvalValue('dental') ?? ''),
            'optha_eent' => (string) ($patient->approvalValue('optha_eent') ?? ''),
            'lab_drugtest' => (string) ($patient->approvalValue('lab_drugtest') ?? ''),
            'ecg_xray' => (string) ($patient->approvalValue('ecg_xray') ?? ''),
            'np' => (string) ($patient->approvalValue('np') ?? ''),
            'gpe' => (string) ($patient->approvalValue('gpe') ?? ''),
        ];

        SyncFinalResultJob::dispatch((string) $appcode, $chiefApproval, $stationApprovals);
    }
}
