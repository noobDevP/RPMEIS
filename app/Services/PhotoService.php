<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientProfile;
use App\Support\AssetPath;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class PhotoService
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/gif'];

    public function storeBase64Image(string $imageData): string
    {
        $normalized = str_replace('data:image/jpeg;base64,', '', $imageData);
        $normalized = str_replace(' ', '+', $normalized);
        $binary = base64_decode($normalized, true);

        if ($binary === false) {
            throw new InvalidArgumentException('Invalid image data.');
        }

        AssetPath::ensureDirectory('profile_pic');

        $filename = 'applicant_'.uniqid().'.jpg';
        $relativePath = 'profile_pic/'.$filename;

        if (file_put_contents(AssetPath::absolute($relativePath), $binary) === false) {
            throw new InvalidArgumentException('Failed to save image.');
        }

        return $relativePath;
    }

    public function reupload(Patient $patient, UploadedFile $file): string
    {
        $mime = $file->getMimeType() ?? '';

        if (! in_array($mime, self::ALLOWED_MIME, true)) {
            throw new InvalidArgumentException('Invalid file type.');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('File too large.');
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            $ext = 'jpg';
        }

        $filename = 'patient_' . $patient->patient_id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $relativePath = 'uploads/patients/' . $filename;

        $file->move(AssetPath::absolute('uploads/patients'), $filename);

        PatientProfile::updateOrCreate(
            ['patient_id' => $patient->patient_id],
            ['photo_path' => $relativePath]
        );

        return $relativePath;
    }

    public function attachToPatient(Patient $patient, string $relativePath): Patient
    {
        PatientProfile::updateOrCreate(
            ['patient_id' => $patient->patient_id],
            ['photo_path' => $relativePath]
        );

        return $patient->refresh();
    }
}
