<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | KoraPay API Credentials
    |--------------------------------------------------------------------------
    */

    'secret_key'     => env('KORA_SECRET_KEY', ''),
    'encryption_key' => env('KORA_ENCRYPTION_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |
    | Supported: "live", "sandbox"
    |--------------------------------------------------------------------------
    */

    'environment' => env('KORA_ENVIRONMENT'),

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |
    | The built-in route is registered outside the web middleware group and
    | requires no CSRF exemption. If you disable register_webhook_route and
    | add the route yourself in routes/web.php, exclude webhook_path from
    | CSRF protection in your app's VerifyCsrfToken middleware (Laravel 10)
    | or bootstrap/app.php (Laravel 11+).
    |--------------------------------------------------------------------------
    */

    'webhook_path'           => env('KORA_WEBHOOK_PATH', 'webhooks/kora'),
    'register_webhook_route' => env('KORA_REGISTER_ROUTE', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */

    'timeout'             => (float) env('KORA_TIMEOUT', 30),
    'connect_timeout'    => (float) env('KORA_CONNECT_TIMEOUT', 10),
    'retry_attempts'     => (int) env('KORA_RETRY_ATTEMPTS', 3),
    'retry_unsafe_methods' => filter_var(
        env('KORA_RETRY_UNSAFE_METHODS', false),
        FILTER_VALIDATE_BOOLEAN,
    ),

];
