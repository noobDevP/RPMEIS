<?php

/**
 * Default PDF positions (mm) for certification signatures on page 2.
 * Override in storage/app/pdf_layout_overrides.json under "signatures" (same shape as dentist / reviewing_officer).
 */
return [
    'physicians' => [
        1 => [
            'name' => ['x' => 58, 'y' => 318, 'w' => 95],
        ],
    ],
    'optometrist' => [
        'name' => ['x' => 58, 'y' => 328, 'w' => 95],
    ],
    'ent' => [
        'name' => ['x' => 58, 'y' => 338, 'w' => 95],
    ],
    'np' => [
        'name' => ['x' => 78, 'y' => 318, 'w' => 95],
    ],
    'ecg' => [
        'name' => ['x' => 58, 'y' => 348, 'w' => 95],
    ],
    'dentist' => [
        'name' => ['x' => 58, 'y' => 338, 'w' => 95],
    ],
    'reviewing_officer' => [
        'name' => ['x' => 58, 'y' => 358, 'w' => 95],
    ],
    'approving_authority' => [
        'name' => ['x' => 58, 'y' => 368, 'w' => 95],
    ],
];