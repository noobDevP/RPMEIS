<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Laravel named routes (filled in as modules are ported)
  |--------------------------------------------------------------------------
  */

  'routes' => [
    'RECRUITER' => 'recruiter.dashboard',
    'REGISTRAR' => 'registrar.index',
    'DENTAL' => 'station.show',
    'GPE' => 'station.show',
    'NP' => 'station.show',
    'LABORATORY' => 'station.show',
    'EENT' => 'station.show',
    'ECG' => 'station.show',
    'DOCTOR' => 'doctor.index',
    'DOCTOR_GPE' => 'doctor.show',
    'DOCTOR_DENTAL' => 'doctor.show',
    'DOCTOR_NP' => 'doctor.show',
    'DOCTOR_ECG_XRAY' => 'doctor.show',
    'DOCTOR_OPTHA_EENT' => 'doctor.show',
    'DOCTOR_LAB_DRUGTEST' => 'doctor.show',
    'EXECUTIVE' => 'executive.processed',
  ],

  'legacy_redirects' => [
    'RECRUITER' => '/recruiter',
    'REGISTRAR' => '/registrar',
    'DENTAL' => '/stations/dental',
    'GPE' => '/stations/gpe',
    'NP' => '/stations/np',
    'LABORATORY' => '/stations/lab_drugtest',
    'EENT' => '/stations/optha_eent',
    'ECG' => '/stations/ecg_xray',
    'DOCTOR' => '/doctor',
    'DOCTOR_GPE' => '/doctor/gpe',
    'DOCTOR_DENTAL' => '/doctor/dental',
    'DOCTOR_NP' => '/doctor/np',
    'DOCTOR_ECG_XRAY' => '/doctor/ecg_xray',
    'DOCTOR_OPTHA_EENT' => '/doctor/optha_eent',
    'DOCTOR_LAB_DRUGTEST' => '/doctor/lab_drugtest',
    'EXECUTIVE' => '/executive/processed',
  ],

];
