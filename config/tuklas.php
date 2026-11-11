<?php

return [
    'admin' => [
        'email' => env('TUKLAS_ADMIN_EMAIL', env('TUKLAS_SUPERADMIN_EMAIL')),
        'name' => env('TUKLAS_ADMIN_NAME', env('TUKLAS_SUPERADMIN_NAME', 'PESO Bugallon Admin')),
        'password' => env('TUKLAS_ADMIN_PASSWORD', env('TUKLAS_SUPERADMIN_PASSWORD')),
    ],
    'demo_password' => env('TUKLAS_DEMO_PASSWORD'),
];
