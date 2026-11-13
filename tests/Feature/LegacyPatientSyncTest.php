<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ClearanceAttachment;
use App\Models\MedicalExamination;
use App\Models\Patient;
use App\Models\ServiceRecord;
use App\Services\LegacyPatientSyncService;
use App\Support\LegacySignatureVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyPatientSyncTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-legacy-shared-secret-2026';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.philarmylab_legacy.url' => 'https://philarmy-lab.test',
            'services.philarmylab_legacy.endpoint' => '/api_sync_export.php',
            'services.philarmylab_legacy.secret' => $this->secret,
            'services.philarmylab_legacy.webhook_tolerance_seconds' => 300,
        ]);

        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_sync_preserves_patient_no_and_handles_collisions_dates_and_stations(): void
    {
        Http::fake([
            'https://philarmy-lab.test/api_sync_export.php*' => function ($request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
                if (($q['resource'] ?? '') === 'file') {
                    return Http::response('fake-binary-content', 200);
                }

                return Http::response([], 404);
            },
        ]);

        $service = app(LegacyPatientSyncService::class);

        $row1 = $this->makeLegacyRow([
            'patient_no' => '114',
            'applicant_no' => '10',
            'serialNo' => '',
            'sex' => 'male',
            'birthdate' => '2001-10-03',
            'dateOfExamination' => '82024-08-28',
            'overall_stat' => 'DONE',
            'status' => 'active',
            'otherData' => 'NA',
            'photo_path' => 'uploads/patients/patient_114_sample.jpg',
            'dental_attachment' => 'uploads/dental_attachments/114_dental.pdf',
            'approval_stat_dental' => 'GO',
            'approval_optha_eent' => 'GO',
            'approval_stat_ecg_xray' => 'GO',
            'approval_stat_lab_drugtest' => 'GO',
            'approval_stat_np' => 'GO',
            'approval_stat_gpe' => 'GO',
        ]);

        $res1 = $service->syncPatientRow($row1);
        $this->assertSame(114, $res1['patient_id']);
        $this->assertTrue($res1['created']);
        $this->assertTrue($res1['photo_synced']);
        $this->assertSame(1, $res1['attachments_synced']);
        $this->assertNotEmpty($res1['warnings']); // date repair warning

        $patient114 = Patient::query()->find(114);
        $this->assertNotNull($patient114);
        $this->assertSame('Male', $patient114->sex);
        $this->assertTrue($patient114->is_activated);
        $this->assertNull($patient114->serviceRecord->serial_no);

        $app114 = $patient114->applications()->first();
        $this->assertSame('10', $app114->applicant_no);
        $this->assertSame('active', $app114->status);

        $exam114 = $app114->latestExamination();
        $this->assertSame('2024-08-28', $exam114->exam_date->format('Y-m-d'));
        $this->assertSame('DONE', $exam114->overall_stat);
        $this->assertSame('PASSED', $exam114->chief_doctor_approval);
        $this->assertSame('NA', $exam114->form_data['legacy_other_data']);

        Storage::disk('public')->assertExists('uploads/patients/patient_114_sample.jpg');
        Storage::disk('public')->assertExists('attachments/114/dental_114_dental.pdf');
        $this->assertSame(1, ClearanceAttachment::query()->count());

        // Second patient has empty serialNo AND duplicate applicant_no='10' AND 'NO GO' station -> FAILED
        $row2 = $this->makeLegacyRow([
            'patient_no' => '1837',
            'applicant_no' => '10',
            'serialNo' => '',
            'sex' => 'female',
            'status' => 'inactive',
            'overall_stat' => 'DONE',
            'approval_stat_np' => 'NO GO',
        ]);

        $res2 = $service->syncPatientRow($row2);
        $this->assertSame(1837, $res2['patient_id']);
        $this->assertTrue($res2['created']);

        $patient1837 = Patient::query()->find(1837);
        $this->assertSame('Female', $patient1837->sex);
        $this->assertTrue($patient1837->is_activated);
        $this->assertSame('10-P1837', $patient1837->applications()->first()->applicant_no);
        $this->assertSame('inactive', $patient1837->applications()->first()->status);
        $this->assertSame('FAILED', $patient1837->applications()->first()->latestExamination()->chief_doctor_approval);

        // Idempotent re-sync of 114 retains its own applicant_no='10' and does not duplicate attachments
        $res1Again = $service->syncPatientRow($row1);
        $this->assertFalse($res1Again['created']);
        $this->assertSame(0, $res1Again['attachments_synced']);
        $this->assertSame('10', Application::query()->where('patient_id', 114)->first()->applicant_no);
        $this->assertSame(2, Patient::query()->count());
        $this->assertSame(2, ServiceRecord::query()->count());
        $this->assertSame(2, MedicalExamination::query()->count());
        $this->assertSame(1, ClearanceAttachment::query()->count());
    }

    public function test_webhook_verifies_hmac_and_processes_upsert_and_delete_events(): void
    {
        Http::fake(['*' => Http::response('img-bytes', 200)]);

        $row = $this->makeLegacyRow([
            'patient_no' => '950',
            'applicant_no' => '950',
            'firstName' => 'JUAN',
            'lastName' => 'DELA CRUZ',
        ]);

        $body = json_encode([
            'event' => 'patient.upserted',
            'sent_at' => time(),
            'patient' => $row,
        ]);

        // Unsigned request rejected with 401
        $this->postJson('/api/legacy-sync/webhook', json_decode($body, true))
            ->assertStatus(401);

        // Signed request accepted and creates patient_id=950
        $verifier = app(LegacySignatureVerifier::class);
        $ts = time();
        $sig = $verifier->sign($body, $ts, $this->secret);

        $this->call(
            'POST',
            '/api/legacy-sync/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_LEGACY_TIMESTAMP' => (string) $ts,
                'HTTP_X_LEGACY_SIGNATURE' => $sig,
            ],
            $body
        )->assertOk()->assertJson([
            'status' => 'ok',
            'patient_id' => 950,
            'created' => true,
        ]);

        $this->assertDatabaseHas('patients', [
            'patient_id' => 950,
            'first_name' => 'JUAN',
            'last_name' => 'DELA CRUZ',
        ]);

        // Signed delete event removes patient cleanly
        $deleteBody = json_encode([
            'event' => 'patient.deleted',
            'sent_at' => time(),
            'patient_no' => 950,
        ]);
        $delTs = time();
        $delSig = $verifier->sign($deleteBody, $delTs, $this->secret);

        $this->call(
            'POST',
            '/api/legacy-sync/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_LEGACY_TIMESTAMP' => (string) $delTs,
                'HTTP_X_LEGACY_SIGNATURE' => $delSig,
            ],
            $deleteBody
        )->assertOk()->assertJson([
            'status' => 'ok',
            'patient_id' => 950,
            'deleted' => true,
        ]);

        $this->assertDatabaseMissing('patients', ['patient_id' => 950]);
    }

    public function test_artisan_sync_command_paginates_remote_api_and_saves_cursor(): void
    {
        Http::fake([
            'https://philarmy-lab.test/api_sync_export.php*' => function ($request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
                if (($q['resource'] ?? '') === 'file') {
                    return Http::response('photo-bytes', 200);
                }
                $after = (int) ($q['after_patient_no'] ?? 0);
                if ($after === 0) {
                    return Http::response([
                        'status' => 'ok',
                        'count' => 1,
                        'has_more' => true,
                        'patients' => [
                            $this->makeLegacyRow([
                                'patient_no' => '200',
                                'applicant_no' => '200',
                                'timestamp_dental' => '2026-11-12 08:00:00',
                            ]),
                        ],
                    ], 200);
                }

                return Http::response([
                    'status' => 'ok',
                    'count' => 1,
                    'has_more' => false,
                    'patients' => [
                        $this->makeLegacyRow([
                            'patient_no' => '201',
                            'applicant_no' => '201',
                            'timestamp_gpe' => '2026-11-13 09:30:00',
                        ]),
                    ],
                ], 200);
            },
        ]);

        $this->artisan('philarmylab:sync', ['--source' => 'api', '--limit' => 1])
            ->assertExitCode(0);

        $this->assertDatabaseHas('patients', ['patient_id' => 200]);
        $this->assertDatabaseHas('patients', ['patient_id' => 201]);

        Storage::disk('local')->assertExists('legacy_sync_state.json');
        $state = json_decode((string) Storage::disk('local')->get('legacy_sync_state.json'), true);
        $this->assertSame(201, $state['max_patient_no']);
        $this->assertSame('2026-11-13 09:30:00', $state['max_timestamp']);
    }

    public function test_artisan_since_last_catches_both_new_null_timestamp_rows_and_updated_rows(): void
    {
        Storage::disk('local')->put('legacy_sync_state.json', json_encode([
            'max_patient_no' => 201,
            'max_timestamp' => '2026-11-13 09:30:00',
        ]));

        Http::fake([
            'https://philarmy-lab.test/api_sync_export.php*' => function ($request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
                $after = (int) ($q['after_patient_no'] ?? 0);

                if (! isset($q['since']) && $after === 201) {
                    return Http::response([
                        'status' => 'ok',
                        'count' => 1,
                        'has_more' => false,
                        'patients' => [
                            $this->makeLegacyRow([
                                'patient_no' => '202',
                                'applicant_no' => '202',
                            ]),
                        ],
                    ], 200);
                }

                if (isset($q['since'])) {
                    return Http::response([
                        'status' => 'ok',
                        'count' => 1,
                        'has_more' => false,
                        'patients' => [
                            $this->makeLegacyRow([
                                'patient_no' => '200',
                                'applicant_no' => '200',
                                'approval_stat_dental' => 'GO',
                                'timestamp_dental' => '2026-11-13 10:00:00',
                            ]),
                        ],
                    ], 200);
                }

                return Http::response([
                    'status' => 'ok',
                    'count' => 0,
                    'has_more' => false,
                    'patients' => [],
                ], 200);
            },
        ]);

        $this->artisan('philarmylab:sync', ['--source' => 'api', '--since-last' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('patients', ['patient_id' => 202]);
        $this->assertDatabaseHas('patients', ['patient_id' => 200]);

        $state = json_decode((string) Storage::disk('local')->get('legacy_sync_state.json'), true);
        $this->assertSame(202, $state['max_patient_no']);
        $this->assertSame('2026-11-13 10:00:00', $state['max_timestamp']);
    }

    public function test_artisan_sync_command_imports_from_sql_dump_and_local_assets(): void
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'legacy_sql_test_' . uniqid();
        mkdir($tmpDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'patients', 0777, true);
        file_put_contents($tmpDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'patients' . DIRECTORY_SEPARATOR . 'p500.jpg', 'local-photo');

        $sqlPath = $tmpDir . DIRECTORY_SEPARATOR . 'dump.sql';
        $sql = <<<'SQL'
CREATE TABLE `patient_data` (
  `patient_no` int(11) NOT NULL,
  `applicant_no` varchar(255) DEFAULT NULL,
  `firstName` varchar(255) DEFAULT NULL,
  `middleName` varchar(255) DEFAULT NULL,
  `lastName` varchar(255) DEFAULT NULL,
  `birthdate` varchar(255) DEFAULT NULL,
  `sex` varchar(255) DEFAULT NULL,
  `dateOfExamination` varchar(255) DEFAULT NULL,
  `overall_stat` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL
);
INSERT INTO `patient_data` (`patient_no`, `applicant_no`, `firstName`, `middleName`, `lastName`, `birthdate`, `sex`, `dateOfExamination`, `overall_stat`, `status`, `photo_path`) VALUES
(500, '500', 'MARIA', 'LOPEZ', 'REYES', '2000-01-15', 'female', '62025-06-17', 'NOT DONE', 'active', 'uploads/patients/p500.jpg');
SQL;
        file_put_contents($sqlPath, $sql);

        try {
            $this->artisan('philarmylab:sync', [
                '--source' => 'sql',
                '--sql-file' => $sqlPath,
                '--assets-dir' => $tmpDir,
            ])->assertExitCode(0);

            $this->assertDatabaseHas('patients', [
                'patient_id' => 500,
                'first_name' => 'MARIA',
                'sex' => 'Female',
            ]);
            Storage::disk('public')->assertExists('uploads/patients/p500.jpg');
        } finally {
            @unlink($sqlPath);
            @unlink($tmpDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'patients' . DIRECTORY_SEPARATOR . 'p500.jpg');
            @rmdir($tmpDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'patients');
            @rmdir($tmpDir . DIRECTORY_SEPARATOR . 'uploads');
            @rmdir($tmpDir);
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function makeLegacyRow(array $overrides = []): array
    {
        return array_merge([
            'patient_no' => '1',
            'rank' => '',
            'serialNo' => '',
            'branch' => '',
            'firstName' => 'PEDRO',
            'middleName' => 'REYES',
            'lastName' => 'SANTOS',
            'birthdate' => '2002-05-10',
            'age' => '24',
            'sex' => 'male',
            'raceSelect' => 'Waray',
            'placeOfBirth' => 'Tacloban City',
            'currentAddress' => 'Camp Lukban',
            'placeOfExamination' => 'Cebu, City',
            'dateOfExamination' => '2026-11-10',
            'purpose' => 'CSC',
            'batch' => 'CS26-8',
            'unitorg' => 'NA',
            'appcode' => 'APP-001',
            'otherData' => 'NA',
            'dental_diagnosis' => null,
            'dental_remarks' => null,
            'approval_stat_dental' => null,
            'dental_attachment' => null,
            'timestamp_dental' => null,
            'optha_eent_diagnosis' => null,
            'optha_eent_remarks' => null,
            'approval_optha_eent' => null,
            'optha_dental_attachment' => null,
            'timestamp_ophtha_eent' => null,
            'ecg_xray_diagnosis' => null,
            'ecg_xray_remarks' => null,
            'approval_stat_ecg_xray' => null,
            'ecg_xray_attachment' => null,
            'timestamp_ecg_xray' => null,
            'drugtest_diagnosis' => null,
            'drugtest_remarks' => null,
            'drugtest_assessment' => 'NO SHOW YET',
            'lab_drugtest_diagnosis' => null,
            'lab_drugtest_remarks' => null,
            'approval_stat_lab_drugtest' => null,
            'lab_drugtest_attachment' => null,
            'timestamp_drugtest_laboratory' => null,
            'np_diagnosis' => null,
            'np_remarks' => null,
            'approval_stat_np' => null,
            'np_neuro_attachment' => null,
            'timestamp_np' => null,
            'gpe_diagnosis' => null,
            'gpe_remarks' => null,
            'approval_stat_gpe' => null,
            'gpe_attachment' => null,
            'timestamp_gpe' => null,
            'doctors_diagnosis' => null,
            'doctors_remarks' => null,
            'status' => 'active',
            'overall_stat' => 'NOT DONE',
            'photo_path' => null,
        ], $overrides);
    }
}
