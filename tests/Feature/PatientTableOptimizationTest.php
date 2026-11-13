<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ClearanceAttachment;
use App\Models\MedicalExamination;
use App\Models\MedicalFormAssessment;
use App\Models\Patient;
use App\Models\ServiceRecord;
use App\Models\Station;
use App\Models\StationClearance;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientTableOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedStations();
    }

    public function test_patient_directory_query_count_stays_constant_as_patient_count_grows(): void
    {
        $this->seedPatients(5);

        $this->withSession([
            'staff.logged_in' => true,
            'staff.role' => 'DOCTOR',
            'staff.display_name' => 'Chief Doctor',
        ]);

        // Warm default lookup initialization (ApprovingAuthority::getAllWithChiefDoctor)
        $this->get(route('doctor.index'))->assertOk();

        $queriesAt5 = $this->measureQueries(fn () => $this->get(route('doctor.index'))->assertOk());

        $this->seedPatients(20, startOffset: 100);

        $queriesAt25 = $this->measureQueries(fn () => $this->get(route('doctor.index'))->assertOk());

        $this->assertSame($queriesAt5, $queriesAt25, 'Patient table query count must not scale with number of patients.');
        $this->assertLessThanOrEqual(20, $queriesAt25);
    }

    public function test_all_patient_table_portals_render_collapsible_filters_within_constant_query_budget(): void
    {
        $this->seedPatients(12);

        $portals = [
            ['role' => 'DOCTOR', 'route' => route('doctor.index'), 'toggleTarget' => 'doctorChiefFilters'],
            ['role' => 'DENTAL', 'route' => route('doctor.show', ['station' => 'dental']), 'toggleTarget' => 'doctorStationFilters'],
            ['role' => 'EXECUTIVE', 'route' => route('executive.processed'), 'toggleTarget' => 'executiveFilters'],
            ['role' => 'REGISTRAR', 'route' => route('registrar.records'), 'toggleTarget' => 'registrarFilters'],
            ['role' => 'RECRUITER', 'route' => route('recruiter.processed'), 'toggleTarget' => 'recruiterFilters'],
            ['role' => 'DENTAL', 'route' => route('station.processed', ['station' => 'dental']), 'toggleTarget' => 'stationProcessedFilters'],
        ];

        foreach ($portals as $portal) {
            $this->withSession([
                'staff.logged_in' => true,
                'staff.role' => $portal['role'],
                'staff.display_name' => 'Test Staff',
            ]);

            $response = null;
            $queryCount = $this->measureQueries(function () use ($portal, &$response) {
                $response = $this->get($portal['route']);
            });

            $response->assertOk();
            $response->assertSee('data-filter-toggle', false);
            $response->assertSee('data-filter-target="' . $portal['toggleTarget'] . '"', false);
            $response->assertSee('id="' . $portal['toggleTarget'] . '" class="filter-collapsible-body is-collapsed"', false);
            $response->assertSee('id="globalBatchFilter"', false);
            $response->assertSee('id="searchInput"', false);
            $this->assertLessThanOrEqual(20, $queryCount, "Too many SQL queries on {$portal['route']}");
        }
    }

    private function measureQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function seedStations(): void
    {
        $stations = [
            ['station_key' => 'gpe', 'station_name' => 'GPE', 'clerk_role' => 'GPE'],
            ['station_key' => 'dental', 'station_name' => 'Dental', 'clerk_role' => 'DENTAL'],
            ['station_key' => 'optha_eent', 'station_name' => 'Optha / EENT', 'clerk_role' => 'EENT'],
            ['station_key' => 'ecg_xray', 'station_name' => 'ECG / X-Ray', 'clerk_role' => 'ECG'],
            ['station_key' => 'lab_drugtest', 'station_name' => 'Lab / Drug Test', 'clerk_role' => 'LABORATORY'],
            ['station_key' => 'np', 'station_name' => 'Neuro-Psychiatric', 'clerk_role' => 'NP'],
        ];

        foreach ($stations as $station) {
            Station::query()->firstOrCreate(
                ['station_key' => $station['station_key']],
                $station
            );
        }
    }

    private function seedPatients(int $count, int $startOffset = 1): void
    {
        $unit = Unit::query()->firstOrCreate(['unit_name' => '2ID PA']);
        $stations = Station::query()->get();

        for ($i = 0; $i < $count; $i++) {
            $num = $startOffset + $i;

            $patient = Patient::query()->create([
                'first_name' => "Juan{$num}",
                'middle_name' => 'D',
                'last_name' => "DelaCruz{$num}",
                'birthdate' => '2000-01-15',
                'sex' => 'Male',
                'is_activated' => true,
            ]);

            ServiceRecord::query()->create([
                'patient_id' => $patient->patient_id,
                'unit_id' => $unit->unit_id,
            ]);

            $application = Application::query()->create([
                'patient_id' => $patient->patient_id,
                'applicant_no' => "APP-{$num}",
                'app_code' => "CODE-{$num}",
                'batch' => 'Batch-2026-A',
                'status' => 'active',
                'purpose' => 'Enlistment',
            ]);

            $exam = MedicalExamination::query()->create([
                'application_id' => $application->application_id,
                'overall_stat' => 'NOT DONE',
                'chief_doctor_approval' => null,
            ]);

            MedicalFormAssessment::query()->create([
                'examination_id' => $exam->examination_id,
                'type' => 'dental',
                'status' => 'completed',
            ]);

            foreach ($stations as $station) {
                $clearance = StationClearance::query()->create([
                    'examination_id' => $exam->examination_id,
                    'station_id' => $station->station_id,
                    'approval_status' => 'GO',
                    'diagnosis' => 'Normal',
                    'remarks' => 'Cleared',
                ]);

                ClearanceAttachment::query()->create([
                    'clearance_id' => $clearance->clearance_id,
                    'file_name' => 'sample.pdf',
                    'file_path' => 'uploads/sample.pdf',
                    'file_type' => 'pdf',
                ]);
            }
        }
    }
}
