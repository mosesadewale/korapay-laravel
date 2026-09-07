<?php

declare(strict_types=1);

namespace Kora\Laravel;

use Illuminate\Support\ServiceProvider;
use Kora\Sdk\Contracts\KoraClientInterface;
use Kora\Sdk\Enums\Environment;
use Kora\Sdk\Factory;
use Kora\Sdk\KoraClient;
use Psr\Log\LoggerInterface;

class KoraServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/kora.php', 'kora');

        $this->app->singleton(KoraClientInterface::class, function ($app): KoraClient {
            $config = $app['config']['kora'];
            $logger = $app->make(LoggerInterface::class);
            $environment = is_string($config['environment'] ?? null) && $config['environment'] !== ''
                ? Environment::from($config['environment'])
                : null;

            return Factory::make(
                secretKey:      $config['secret_key'],
                encryptionKey:  $config['encryption_key'],
                environment:    $environment,
                timeout:        (float) $config['timeout'],
                retryAttempts:  (int)   $config['retry_attempts'],
                connectTimeout: (float) ($config['connect_timeout'] ?? 10),
                retryUnsafeMethods: filter_var(
                    $config['retry_unsafe_methods'] ?? false,
                    FILTER_VALIDATE_BOOLEAN,
                ),
                logger:         $logger,
            );
        });

        $this->app->alias(KoraClientInterface::class, KoraClient::class);
    }

    public function boot(): void
    {
        if (config('kora.register_webhook_route', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/webhooks.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/kora.php' => config_path('kora.php'),
            ], 'kora-config');
        }
    }
}
