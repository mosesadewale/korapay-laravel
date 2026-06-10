<?php

declare(strict_types=1);

namespace Kora\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Kora\Laravel\Tests\TestCase;
use Kora\Sdk\Contracts\KoraClientInterface;
use Kora\Sdk\KoraClient;
use PHPUnit\Framework\Attributes\Test;

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
    }

    #[Test]
    public function webhook_route_is_not_registered_by_default(): void
    {
        self::assertFalse(Route::has('kora.webhook'));
    }
}
