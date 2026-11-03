<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; }
        h1 { font-size: 18px; margin-bottom: 8px; }
        p { margin: 4px 0; font-size: 12px; }
        .box { border: 1px solid #d1d5db; padding: 10px; border-radius: 6px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="box">
        <p><strong>Patient No:</strong> {{ $patient->patient_id ?? 'N/A' }}</p>
        <p><strong>Name:</strong> {{ trim(implode(' ', array_filter([$patient->first_name, $patient->middle_name, $patient->last_name]))) }}</p>
        <p><strong>Status:</strong> {{ $patient->applications->first()?->status ?? 'N/A' }}</p>
        <p><strong>Batch:</strong> {{ $patient->applications->first()?->batch ?? 'N/A' }}</p>
        <p><strong>Applicant No:</strong> {{ ($patient->applications->first()?->applicant_no) ?? 'N/A' }}</p>
    </div>
</body>
</html>

