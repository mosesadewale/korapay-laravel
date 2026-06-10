<?php

declare(strict_types=1);

namespace Kora\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Kora\Laravel\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class WebhookRouteEnabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('kora.register_webhook_route', true);
    }

    #[Test]
    public function webhook_route_is_registered_when_register_route_is_true(): void
    {
        self::assertTrue(Route::has('kora.webhook'));
    }
}
