<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', 'http://localhost/api/auth/google/callback'),
    ],

    'stripe' => [
        'price_pro' => env('STRIPE_PRICE_PRO'),
    ],
];
