<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mux API Credentials
    |--------------------------------------------------------------------------
    |
    | These are your Mux Access Token ID and Secret Key from the Mux Dashboard.
    |
    */
    'access_token' => env('MUX_ACCESS_TOKEN', ''),
    'secret_key' => env('MUX_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Set the environment for Mux usage (e.g., 'production' or 'development').
    |
    */
    'environment' => env('MUX_ENVIRONMENT', 'production'),
];
