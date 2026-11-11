<?php

return [
    'sample_catalog' => [
        'allow_in_production' => env('TUKLAS_ALLOW_SAMPLE_CATALOG_IN_PRODUCTION', false),
    ],
    'superadmin' => [
        'email' => env('TUKLAS_SUPERADMIN_EMAIL'),
        'name' => env('TUKLAS_SUPERADMIN_NAME', 'PESO Bugallon'),
        'password' => env('TUKLAS_SUPERADMIN_PASSWORD'),
    ],
];
