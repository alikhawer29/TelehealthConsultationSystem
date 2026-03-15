<?php

return [
    'stripe' => [
        'credentials' => [
            'private_key' => env('PRIVATE_KEY', null),
            'public_key' => env('PUBLIC_KEY', null),
            'additional' => [],
        ],
    ]
];
