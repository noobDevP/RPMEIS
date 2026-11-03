<?php

namespace App\Services;

use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PdfService
{
    public function streamForPatient(int $patientNo): Response
    {
        $patient = Patient::query()->find($patientNo);

        if (! $patient) {
            abort(404, 'Patient not found.');
        }

        $html = view('pdf.patient', [
            'patient' => $patient,
            'title' => 'Applicant Medical Summary',
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->stream('patient-'.$patientNo.'.pdf');
    }
}
