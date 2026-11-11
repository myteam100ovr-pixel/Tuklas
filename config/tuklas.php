<?php

return [
    'admin' => [
        'email' => env('TUKLAS_ADMIN_EMAIL', env('TUKLAS_SUPERADMIN_EMAIL')),
        'name' => env('TUKLAS_ADMIN_NAME', env('TUKLAS_SUPERADMIN_NAME', 'PESO Bugallon Admin')),
        'password' => env('TUKLAS_ADMIN_PASSWORD', env('TUKLAS_SUPERADMIN_PASSWORD')),
    ],
    'demo_password' => env('TUKLAS_DEMO_PASSWORD'),
    'demo_accounts' => [
        'career_seeker_email' => env('TUKLAS_DEMO_CAREER_SEEKER_EMAIL', 'demo-youth@tuklas.test'),
        'career_seeker_password' => env('TUKLAS_DEMO_CAREER_SEEKER_PASSWORD', env('TUKLAS_DEMO_PASSWORD')),
        'admin_email' => env('TUKLAS_DEMO_ADMIN_EMAIL', 'demo-admin@tuklas.test'),
        'admin_password' => env('TUKLAS_DEMO_ADMIN_PASSWORD'),
        'tesda_email' => env('TUKLAS_DEMO_TESDA_EMAIL', 'demo-tesda@tuklas.test'),
        'tesda_password' => env('TUKLAS_DEMO_TESDA_PASSWORD'),
    ],
];
