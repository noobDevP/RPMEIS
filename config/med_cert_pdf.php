<?php

return [
    'template' => storage_path('app/med_cert_template.pdf'),
    'page' => [
        'width_mm' => 210,
        'height_mm' => 297,
        'content_scale' => 1.0,
        'offset_x_mm' => 0,
        'offset_y_mm' => 0,
    ],
    'x_offset' => 0,
    'y_offset' => 0,
    'font' => [
        'family' => 'Helvetica',
        'size' => 12,
    ],
    'fields' => [
        // Top fields
        'full_name' => ['x' => 50, 'y' => 50, 'w' => 200],
        'date_1' => ['x' => 50, 'y' => 60, 'w' => 200],
        'date_2' => ['x' => 50, 'y' => 70, 'w' => 200],
        'date_3' => ['x' => 50, 'y' => 80, 'w' => 200],
        'physical_profile' => ['x' => 50, 'y' => 90, 'w' => 200],
        'remarks' => ['x' => 50, 'y' => 110, 'w' => 200],
        // Bottom doctor fields
        'doctor_name' => ['x' => 50, 'y' => 250, 'w' => 200],
        'doctor_rank' => ['x' => 50, 'y' => 270, 'w' => 200],
        'doctor_afpos' => ['x' => 90, 'y' => 270, 'w' => 200],
        'doctor_license' => ['x' => 50, 'y' => 290, 'w' => 200],
    ],
];