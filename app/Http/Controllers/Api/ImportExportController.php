<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ImportExportController extends Controller
{
    protected PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    public function import(Request $request)
    {
        if (!in_array(session('staff.role'), ['RECRUITER'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Normalize header row to map to data keys
        $headerMap = [];
        if ($header) {
            foreach ($header as $col) {
                $headerMap[] = strtolower(trim($col));
            }
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($headerMap) !== count($row)) {
                $errorCount++;
                continue;
            }
            $data = array_combine($headerMap, $row);

            try {
                // Map CSV data to PatientService create format
                // Required fields: applicant_no, firstName, lastName
                if (empty($data['applicant no']) || empty($data['first name']) || empty($data['last name'])) {
                    $errorCount++;
                    $errors[] = "Missing required fields for row: " . json_encode($row);
                    continue;
                }

                $patientData = [
                    'applicant_no' => $data['applicant no'],
                    'rank' => $data['rank'] ?? null,
                    'serialNo' => $data['serial no'] ?? null,
                    'branch' => $data['branch'] ?? null,
                    'firstName' => $data['first name'],
                    'middleName' => $data['middle name'] ?? null,
                    'lastName' => $data['last name'],
                    'birthdate' => $data['birthdate'] ?? null, // Expected format YYYY-MM-DD
                    'age' => $data['age'] ?? null,
                    'sex' => $data['sex'] ?? null,
                    'raceSelect' => $data['race'] ?? null,
                    'placeOfBirth' => $data['place of birth'] ?? null,
                    'currentAddress' => $data['current address'] ?? null,
                    'placeOfExamination' => $data['place of examination'] ?? null,
                    'dateOfExamination' => $data['date of examination'] ?? null,
                    'purpose' => $data['purpose'] ?? null,
                    'batch' => $data['batch'] ?? null,
                    'unitorg' => $data['unit/org'] ?? null,
                    'appcode' => $data['applicant code'] ?? null,
                ];

                $this->patientService->create($patientData);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = $e->getMessage();
            }
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'message' => "Import completed. {$successCount} applicants imported successfully. {$errorCount} failed.",
            'errors' => $errors
        ]);
    }

    public function export(Request $request)
    {
        if (!in_array(session('staff.role'), ['RECRUITER', 'REGISTRAR', 'EXECUTIVE'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'batch' => 'required|string'
        ]);

        $batch = $request->input('batch');

        $patients = Patient::with(['applications.examinations.clearances.station', 'serviceRecord.rank', 'serviceRecord.unit.branch'])
            ->whereHas('applications', fn ($q) => $q->where('batch', $batch))
            ->orderBy('patient_id', 'desc')
            ->get();

        // Archive the batch
        \App\Models\Application::where('batch', $batch)->update(['status' => 'inactive']);

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=applicants_export_" . date('Y-m-d_His') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Patient No', 'Applicant No', 'Rank', 'Serial No', 'Branch', 
            'First Name', 'Middle Name', 'Last Name', 'Birthdate', 'Age', 
            'Sex', 'Batch', 'Unit/Org', 'Applicant Code', 
            'Overall Status', 'Chief Doctor Approval',
            'GPE Approval', 'Dental Approval', 'Ocular/EENT Approval', 
            'ECG/Xray Approval', 'Lab/Drug Test Approval', 'NP Approval'
        ];

        $callback = function() use($patients, $columns, $batch) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($patients as $patient) {
                $app = $patient->applications->where('batch', $batch)->first() ?? $patient->applications->first();
                $exam = $app?->latestExamination();
                $service = $patient->serviceRecord;

                $row = [
                    $patient->patient_id,
                    $app?->applicant_no ?? '',
                    $service?->rank?->rank_name ?? '',
                    $service?->serial_no ?? '',
                    $service?->unit?->branch?->branch_name ?? '',
                    $patient->first_name,
                    $patient->middle_name,
                    $patient->last_name,
                    $patient->birthdate?->format('Y-m-d') ?? '',
                    $patient->age,
                    $patient->sex,
                    $app?->batch ?? '',
                    $service?->unit?->unit_name ?? '',
                    $app?->app_code ?? '',
                    $exam?->overall_stat ?? 'NOT DONE',
                    $exam?->chief_doctor_approval ?? 'N/A',
                    $patient->approvalValue('gpe') ?? '',
                    $patient->approvalValue('dental') ?? '',
                    $patient->approvalValue('optha_eent') ?? '',
                    $patient->approvalValue('ecg_xray') ?? '',
                    $patient->approvalValue('lab_drugtest') ?? '',
                    $patient->approvalValue('np') ?? '',
                ];

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
