<?php

return [
    // Toggle: 'mock' for testing without KRA credentials, 'oscu' for the real thing
    'driver' => env('ETIMS_DRIVER', 'mock'),

    'oscu' => [
        'base_url' => env('ETIMS_OSCU_BASE_URL'),
        'pin' => env('ETIMS_KRA_PIN'),
        'branch_id' => env('ETIMS_BRANCH_ID', '00'),
        'device_serial' => env('ETIMS_DEVICE_SERIAL'),
        // The CMC key is issued by KRA during device initialization —
        // store it once you have it, never hardcode it
        'cmc_key' => env('ETIMS_CMC_KEY'),
    ],
];