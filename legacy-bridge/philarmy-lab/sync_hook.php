<?php

/**
 * Real-Time Outbound Sync Hook for philarmy-lab.com -> rpm-is.com
 *
 * Included once at the bottom of config.php via:
 *   include_once __DIR__ . '/sync_hook.php';
 */

if (defined('PHILARMYLAB_SYNC_HOOK_REGISTERED')) {
    return;
}
define('PHILARMYLAB_SYNC_HOOK_REGISTERED', true);

(static function () use ($host, $username, $password, $database): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $scriptName = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $mutationScripts = [
        'delete_applicant.php',
        'reactivate_applicant.php',
        'reupload_photo.php',
        'savePatientData.php',
        'update_dental_data.php',
        'update_ecg_xray_data.php',
        'update_gpe_data.php',
        'update_lab_drugtest_data.php',
        'update_neuro_data.php',
        'update_optha_eent_data.php',
        'update_patient.php',
        'update_patient_data.php',
        'update_patient_data_ecg_xray.php',
        'update_patient_data_gpe.php',
        'update_patient_data_np.php',
        'update_patient_data_optha_eent.php',
        'update_patient_lab_drugtest.php',
        'update_patient_status.php',
        'update_status.php',
        'upload_dental_attachment.php',
        'upload_ecg_xray_attachment.php',
        'upload_gpe_attachment.php',
        'upload_lab_drugtest_attachment.php',
        'upload_neuro_attachment.php',
        'upload_optha_eent_attachment.php',
    ];

    if (! in_array($scriptName, $mutationScripts, true)) {
        return;
    }

    $syncConfigFile = __DIR__ . '/sync_config.php';
    if (! is_file($syncConfigFile)) {
        return;
    }

    $syncConfig = require $syncConfigFile;
    if (empty($syncConfig['enable_webhook']) || empty($syncConfig['secret']) || $syncConfig['secret'] === 'REPLACE_WITH_SHARED_SECRET' || empty($syncConfig['rpmis_webhook_url'])) {
        return;
    }

    $postSnapshot = $_POST;
    $rawInputSnapshot = $scriptName === 'savePatientData.php' ? @file_get_contents('php://input') : null;

    register_shutdown_function(static function () use ($scriptName, $syncConfig, $host, $username, $password, $database, $postSnapshot, $rawInputSnapshot): void {
        if (http_response_code() >= 400) {
            return;
        }

        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            @litespeed_finish_request();
        }

        if (! class_exists('mysqli') || ! function_exists('curl_init')) {
            return;
        }

        $db = @new mysqli($host, $username, $password, $database);
        if ($db->connect_error) {
            return;
        }
        @$db->set_charset('utf8mb4');

        $payload = null;

        try {
            if ($scriptName === 'delete_applicant.php') {
                $patientNo = isset($postSnapshot['patient_no']) && ctype_digit((string) $postSnapshot['patient_no'])
                    ? (int) $postSnapshot['patient_no']
                    : 0;
                if ($patientNo > 0) {
                    $stmt = $db->prepare('SELECT patient_no FROM patient_data WHERE patient_no = ? LIMIT 1');
                    $stmt->bind_param('i', $patientNo);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $stillExists = $res && $res->num_rows > 0;
                    $stmt->close();

                    if (! $stillExists) {
                        $payload = [
                            'event' => 'patient.deleted',
                            'sent_at' => time(),
                            'patient_no' => $patientNo,
                        ];
                    }
                }
            } elseif ($scriptName === 'savePatientData.php') {
                $data = is_string($rawInputSnapshot) ? json_decode($rawInputSnapshot, true) : null;
                if (is_array($data)) {
                    $applicantNo = (string) ($data['applicant_no'] ?? '');
                    $firstName = (string) ($data['firstName'] ?? '');
                    $lastName = (string) ($data['lastName'] ?? '');
                    $birthdate = (string) ($data['birthdate'] ?? '');
                    $stmt = $db->prepare('SELECT * FROM patient_data WHERE applicant_no = ? AND firstName = ? AND lastName = ? AND birthdate = ? ORDER BY patient_no DESC LIMIT 1');
                    if ($stmt) {
                        $stmt->bind_param('ssss', $applicantNo, $firstName, $lastName, $birthdate);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        $row = $res ? $res->fetch_assoc() : null;
                        $stmt->close();
                        if ($row) {
                            $payload = [
                                'event' => 'patient.upserted',
                                'sent_at' => time(),
                                'patient' => $row,
                            ];
                        }
                    }
                }
            } elseif ($scriptName === 'update_status.php') {
                $firstName = (string) ($postSnapshot['firstName'] ?? '');
                $middleName = (string) ($postSnapshot['middleName'] ?? '');
                $lastName = (string) ($postSnapshot['lastName'] ?? '');
                $birthdate = (string) ($postSnapshot['birthdate'] ?? '');
                $stmt = $db->prepare('SELECT * FROM patient_data WHERE firstName = ? AND middleName = ? AND lastName = ? AND birthdate = ? ORDER BY patient_no DESC LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('ssss', $firstName, $middleName, $lastName, $birthdate);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $row = $res ? $res->fetch_assoc() : null;
                    $stmt->close();
                    if ($row) {
                        $payload = [
                            'event' => 'patient.upserted',
                            'sent_at' => time(),
                            'patient' => $row,
                        ];
                    }
                }
            } else {
                $patientNo = isset($postSnapshot['patient_no']) ? (int) $postSnapshot['patient_no'] : 0;
                if ($patientNo > 0) {
                    $stmt = $db->prepare('SELECT * FROM patient_data WHERE patient_no = ? LIMIT 1');
                    if ($stmt) {
                        $stmt->bind_param('i', $patientNo);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        $row = $res ? $res->fetch_assoc() : null;
                        $stmt->close();
                        if ($row) {
                            $payload = [
                                'event' => 'patient.upserted',
                                'sent_at' => time(),
                                'patient' => $row,
                            ];
                        }
                    }
                }
            }
        } finally {
            @$db->close();
        }

        if ($payload === null) {
            return;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! is_string($json) || $json === '') {
            return;
        }

        $ts = (string) time();
        $sig = hash_hmac('sha256', $ts . '.' . $json, (string) $syncConfig['secret']);

        $ch = curl_init((string) $syncConfig['rpmis_webhook_url']);
        if ($ch === false) {
            return;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-Legacy-Timestamp: ' . $ts,
                'X-Legacy-Signature: ' . $sig,
            ],
            CURLOPT_CONNECTTIMEOUT_MS => (int) ($syncConfig['connect_timeout_ms'] ?? 1500),
            CURLOPT_TIMEOUT_MS => (int) ($syncConfig['timeout_ms'] ?? 3000),
        ]);
        @curl_exec($ch);
        @curl_close($ch);
    });
})();
