<?php

declare(strict_types=1);

namespace Kora\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Kora\Laravel\Tests\TestCase;
use Kora\Sdk\Contracts\KoraClientInterface;
use Kora\Sdk\KoraClient;
use Kora\Sdk\Support\KoraConfig;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

final class ServiceProviderTest extends TestCase
{
    #[Test]
    public function kora_client_is_registered_as_singleton(): void
    {
        $a = $this->app->make(KoraClient::class);
        $b = $this->app->make(KoraClient::class);

        self::assertSame($a, $b);
    }

    #[Test]
    public function kora_client_interface_alias_resolves(): void
    {
        self::assertInstanceOf(KoraClient::class, $this->app->make(KoraClientInterface::class));
    }

    #[Test]
    public function config_is_merged(): void
    {
        self::assertNotNull(config('kora.secret_key'));
        self::assertNotNull(config('kora.webhook_path'));
        self::assertNotNull(config('kora.retry_attempts'));
        self::assertSame(10.0, config('kora.connect_timeout'));
        self::assertFalse(config('kora.retry_unsafe_methods'));
    }

    #[Test]
    public function sdk_http_options_are_forwarded(): void
    {
        $this->app['config']->set('kora.connect_timeout', 4.25);
        $this->app['config']->set('kora.retry_unsafe_methods', true);

        $client = $this->app->make(KoraClientInterface::class);
        $clientReflection = new ReflectionClass($client);
        $http = $clientReflection->getProperty('http')->getValue($client);
        $httpReflection = new ReflectionClass($http);
        $config = $httpReflection->getProperty('config')->getValue($http);

        self::assertInstanceOf(KoraConfig::class, $config);
        self::assertSame(4.25, $config->connectTimeout);
        self::assertTrue($config->retryUnsafeMethods);
    }

    #[Test]
    public function invalid_sdk_http_options_are_rejected_by_core_configuration(): void
    {
        $this->app['config']->set('kora.connect_timeout', 0);

        $this->expectException(\InvalidArgumentException::class);

        $this->app->make(KoraClientInterface::class);
    }

    #[Test]
    public function webhook_route_is_not_registered_by_default(): void
    {
        self::assertFalse(Route::has('kora.webhook'));
    }
}
