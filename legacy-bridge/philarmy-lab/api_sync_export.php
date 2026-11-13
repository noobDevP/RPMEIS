<?php

/**
 * Authenticated Read-Only Export & Asset Bridge for rpm-is.com
 * Upload to public_html/api_sync_export.php on philarmy-lab.com
 */

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$syncConfigFile = __DIR__ . '/sync_config.php';
if (! is_file($syncConfigFile)) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Sync configuration missing.']);
    exit;
}

$syncConfig = require $syncConfigFile;
$secret = isset($syncConfig['secret']) ? (string) $syncConfig['secret'] : '';
$tolerance = isset($syncConfig['tolerance_seconds']) ? (int) $syncConfig['tolerance_seconds'] : 300;

if ($secret === '' || $secret === 'REPLACE_WITH_SHARED_SECRET') {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Sync secret not configured.']);
    exit;
}

$timestampHeader = $_SERVER['HTTP_X_LEGACY_TIMESTAMP'] ?? '';
$signatureHeader = $_SERVER['HTTP_X_LEGACY_SIGNATURE'] ?? '';

if (! ctype_digit((string) $timestampHeader) || abs(time() - (int) $timestampHeader) > max(1, $tolerance)) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: invalid or expired timestamp.']);
    exit;
}

$rawQuery = (string) ($_SERVER['QUERY_STRING'] ?? '');
$expectedSig = hash_hmac('sha256', $timestampHeader . '.' . $rawQuery, $secret);
$providedSig = strtolower(trim((string) $signatureHeader));
if (str_starts_with($providedSig, 'sha256=')) {
    $providedSig = substr($providedSig, 7);
}

if (! hash_equals($expectedSig, $providedSig)) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: invalid signature.']);
    exit;
}

$resource = $_GET['resource'] ?? 'patients';

if ($resource === 'file') {
    $requestedPath = str_replace('\\', '/', ltrim((string) ($_GET['path'] ?? ''), '/'));
    if ($requestedPath === '' || str_contains($requestedPath, '..') || str_contains($requestedPath, "\0")) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Invalid file path.']);
        exit;
    }

    $allowedDirs = $syncConfig['allowed_upload_dirs'] ?? [];
    $isAllowedPrefix = false;
    foreach ($allowedDirs as $prefix) {
        if (str_starts_with($requestedPath, $prefix)) {
            $isAllowedPrefix = true;
            break;
        }
    }

    $baseDir = realpath(__DIR__);
    $fullPath = realpath(__DIR__ . '/' . $requestedPath);
    if (! $isAllowedPrefix || $baseDir === false || $fullPath === false || ! str_starts_with($fullPath, $baseDir . DIRECTORY_SEPARATOR) || ! is_file($fullPath)) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'File not found.']);
        exit;
    }

    $mime = function_exists('mime_content_type') ? @mime_content_type($fullPath) : false;
    if (! is_string($mime) || $mime === '') {
        $mime = 'application/octet-stream';
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($fullPath));
    readfile($fullPath);
    exit;
}

if ($resource !== 'patients') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Unsupported resource.']);
    exit;
}

ob_start();
include __DIR__ . '/config.php';
if (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
$respondJson = static function (array $payload, int $statusCode = 200) use ($jsonFlags): void {
    $encoded = json_encode($payload, $jsonFlags);
    if ($encoded === false) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'JSON encoding failed: ' . json_last_error_msg(),
        ]);
        exit;
    }

    http_response_code($statusCode);
    echo $encoded;
    exit;
};

$fetchStmtRows = static function (mysqli_stmt $stmt): array {
    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        $out = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $out[] = $r;
            }
        }

        return $out;
    }

    $stmt->store_result();
    $meta = $stmt->result_metadata();
    if (! $meta) {
        return [];
    }

    $fields = $meta->fetch_fields();
    $meta->free();
    $row = [];
    $refs = [];
    foreach ($fields as $f) {
        $row[$f->name] = null;
        $refs[] = &$row[$f->name];
    }
    call_user_func_array([$stmt, 'bind_result'], $refs);

    $out = [];
    while ($stmt->fetch()) {
        $copy = [];
        foreach ($row as $k => $v) {
            $copy[$k] = $v;
        }
        $out[] = $copy;
    }

    return $out;
};

$patientNo = isset($_GET['patient_no']) && ctype_digit((string) $_GET['patient_no']) ? (int) $_GET['patient_no'] : null;
$afterPatientNo = isset($_GET['after_patient_no']) && ctype_digit((string) $_GET['after_patient_no']) ? (int) $_GET['after_patient_no'] : 0;
$limit = isset($_GET['limit']) && ctype_digit((string) $_GET['limit']) ? max(1, min(500, (int) $_GET['limit'])) : 100;
$since = isset($_GET['since']) ? trim((string) $_GET['since']) : '';

if ($patientNo !== null) {
    $stmt = $conn->prepare('SELECT * FROM patient_data WHERE patient_no = ? LIMIT 1');
    if (! $stmt) {
        $conn->close();
        $respondJson(['status' => 'error', 'message' => 'Query prepare failed.'], 500);
    }
    $stmt->bind_param('i', $patientNo);
    $stmt->execute();
    $rows = $fetchStmtRows($stmt);
    $stmt->close();
    $conn->close();

    $respondJson([
        'status' => 'ok',
        'count' => count($rows),
        'has_more' => false,
        'patients' => $rows,
    ]);
}

$fetchLimit = $limit + 1;

if ($since !== '' && preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?$/', $since) === 1) {
    $sinceNormalized = str_replace('T', ' ', $since);
    $sql = "SELECT * FROM patient_data
            WHERE patient_no > ?
              AND GREATEST(
                  COALESCE(NULLIF(timestamp_dental, ''), '1970-01-01 00:00:00'),
                  COALESCE(NULLIF(timestamp_ophtha_eent, ''), '1970-01-01 00:00:00'),
                  COALESCE(NULLIF(timestamp_ecg_xray, ''), '1970-01-01 00:00:00'),
                  COALESCE(NULLIF(timestamp_drugtest_laboratory, ''), '1970-01-01 00:00:00'),
                  COALESCE(NULLIF(timestamp_np, ''), '1970-01-01 00:00:00'),
                  COALESCE(NULLIF(timestamp_gpe, ''), '1970-01-01 00:00:00')
              ) >= ?
            ORDER BY patient_no ASC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    if (! $stmt) {
        $conn->close();
        $respondJson(['status' => 'error', 'message' => 'Query prepare failed.'], 500);
    }
    $stmt->bind_param('isi', $afterPatientNo, $sinceNormalized, $fetchLimit);
} else {
    $sql = 'SELECT * FROM patient_data WHERE patient_no > ? ORDER BY patient_no ASC LIMIT ?';
    $stmt = $conn->prepare($sql);
    if (! $stmt) {
        $conn->close();
        $respondJson(['status' => 'error', 'message' => 'Query prepare failed.'], 500);
    }
    $stmt->bind_param('ii', $afterPatientNo, $fetchLimit);
}

$stmt->execute();
$rows = $fetchStmtRows($stmt);
$stmt->close();
$conn->close();

$hasMore = count($rows) > $limit;
if ($hasMore) {
    array_pop($rows);
}

$respondJson([
    'status' => 'ok',
    'count' => count($rows),
    'has_more' => $hasMore,
    'patients' => $rows,
]);
