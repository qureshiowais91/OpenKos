<?php

/*
|--------------------------------------------------------------------------
| GrihaFlow Regional Configuration
|--------------------------------------------------------------------------
|
| The single authoritative source of truth for every India-specific
| setting in the application. Backend services, validation rules, seeders,
| installer defaults, database migration defaults and the Inertia shared
| props consumed by the frontend all read from this file.
|
| GrihaFlow is an India-only application. Do not hardcode regional values
| elsewhere and do not add multi-country or multi-currency support here.
|
*/

return [

    'country' => [
        'name' => 'India',
        'code' => 'IN',
        'phone_code' => '+91',
        'postal_code_label' => 'PIN code',
        'postal_code_pattern' => '[1-9]\d{5}',
    ],

    'currency' => [
        'code' => 'INR',
        'symbol' => '₹',
        'minor_units' => 2,
        'supported' => ['INR'],
    ],

    'timezone' => 'Asia/Kolkata',

    'locale' => [
        'app' => 'en',
        'intl' => 'en-IN',
        'fallback' => 'en',
        'options' => [
            'en' => 'English',
        ],
        // Subtags that resolve to the application locale.
        'aliases' => [
            'en' => 'en',
            'en-us' => 'en',
            'en-in' => 'en',
        ],
    ],

    'format' => [
        'date_format' => 'DD/MM/YYYY',
        'time_format' => '12h',
        'numbering' => 'indian',
        'measurement' => 'metric',
    ],

];
