<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\SigningDoctor;
use App\Services\MedCertPdfService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PdfController extends Controller
{
    public function __construct(
        private PdfService $pdf,
        private MedCertPdfService $medCertPdf,
    ) {}

    public function show(int $patientNo): Response
    {
        \App\Services\ActivityLogger::log('Downloaded Redform', $patientNo);
        return $this->pdf->streamForPatient($patientNo);
    }

    public function medCert(Request $request): Response
    {
        $request->validate([
            'patient_no' => 'required|integer',
            'is_manual' => 'nullable|boolean',
            'signing_doctor_id' => 'required_without:is_manual|nullable|exists:signing_doctors,id',
            'manual_name' => 'required_if:is_manual,true|nullable|string',
            'manual_rank' => 'required_if:is_manual,true|nullable|string',
            'manual_afpos' => 'nullable|string',
            'manual_status' => 'nullable|string',
            'manual_position' => 'required_if:is_manual,true|nullable|string',
        ]);

        $patient = Patient::query()->findOrFail($request->patient_no);
        \App\Services\ActivityLogger::log('Downloaded Medical Certificate', $request->patient_no);

        if ($request->boolean('is_manual')) {
            $name = $request->manual_name;
            $rank = $request->manual_rank;
            $afpos = $request->manual_afpos;
            $status = $request->manual_status;
            $position = $request->manual_position;
        } else {
            $doctor = SigningDoctor::query()->findOrFail($request->signing_doctor_id);
            $name = $doctor->name;
            $rank = $doctor->rank;
            $afpos = $doctor->afpos;
            $status = $doctor->status;
            $position = $doctor->position;
        }

        // Format the AFPOS string
        $afposStr = "";
        if (!empty($afpos) && !empty($status)) {
            $afposStr = $afpos . " (" . $status . ") PA";
        } elseif (empty($afpos) && !empty($status)) {
            $afposStr = "(" . trim($status, "()") . ") PA";
        } else {
            if (!empty($afpos)) {
                $afposStr = "(" . trim($afpos, "()") . ") PA";
            } else {
                $afposStr = "PA";
            }
        }
        $afposStr = strtoupper($afposStr);
        $name = strtoupper(preg_replace('/^DR\.?\s+/i', '', $name));

        $pdfContent = $this->medCertPdf->generate($patient, [
            'name' => $name,
            'rank' => strtoupper($rank),
            'afpos' => $afposStr,
            'license' => $position, // Passing position into 'license' which maps to the 3rd line
        ]);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="medical-certificate-'.$patient->patient_id.'.pdf"',
        ]);
    }
}
