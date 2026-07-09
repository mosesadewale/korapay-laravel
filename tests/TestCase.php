<?php

declare(strict_types=1);

namespace Kora\Laravel\Tests;

use Kora\Laravel\KoraServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [KoraServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('kora.secret_key', 'sk_test_webhook_signing_key');
        $app['config']->set('kora.environment', 'sandbox');
        $app['config']->set('kora.encryption_key', '');
        $app['config']->set('kora.timeout', 30);
        $app['config']->set('kora.retry_attempts', 0);
    }
}
