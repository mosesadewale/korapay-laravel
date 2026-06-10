<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kora\Laravel\Http\Controllers\KoraWebhookController;
use Kora\Laravel\Http\Middleware\VerifyKoraWebhook;

Route::post(
    config('kora.webhook_path', 'webhooks/kora'),
    [KoraWebhookController::class, 'handle'],
)->middleware(VerifyKoraWebhook::class)->name('kora.webhook');
