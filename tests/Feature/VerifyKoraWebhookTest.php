<?php

declare(strict_types=1);

namespace Kora\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Kora\Laravel\Events\KoraWebhookReceived;
use Kora\Laravel\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class VerifyKoraWebhookTest extends TestCase
{
    private const SECRET = 'sk_test_webhook_signing_key';

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('kora.register_webhook_route', true);
    }

    /** @param array<string, mixed> $data */
    private function sign(array $data): string
    {
        return hash_hmac('sha256', json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), self::SECRET);
    }

    #[Test]
    public function valid_signature_passes_through(): void
    {
        $data    = ['reference' => 'ref_001', 'status' => 'success'];
        $payload = json_encode(['event' => 'charge.success', 'data' => $data], JSON_THROW_ON_ERROR);
        $sig     = $this->sign($data);

        $response = $this->call('POST', config('kora.webhook_path'), [], [], [], [
            'HTTP_X_KORAPAY_SIGNATURE' => $sig,
            'CONTENT_TYPE'             => 'application/json',
        ], $payload);

        $response->assertStatus(200);
    }

    #[Test]
    public function invalid_signature_is_acknowledged(): void
    {
        Event::fake();

        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'ref_001']], JSON_THROW_ON_ERROR);

        $response = $this->call('POST', config('kora.webhook_path'), [], [], [], [
            'HTTP_X_KORAPAY_SIGNATURE' => 'bad_signature',
            'CONTENT_TYPE'             => 'application/json',
        ], $payload);

        $response->assertOk()->assertJson(['received' => false]);
        Event::assertNotDispatched(KoraWebhookReceived::class);
    }

    #[Test]
    public function route_is_not_subject_to_csrf(): void
    {
        // A 200 acknowledgement confirms CSRF did not intercept the webhook route.
        $response = $this->call('POST', config('kora.webhook_path'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{}');

        $response->assertOk()->assertJson(['received' => false]);
    }

    #[Test]
    public function missing_signature_is_acknowledged(): void
    {
        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'ref_001']], JSON_THROW_ON_ERROR);

        $response = $this->call('POST', config('kora.webhook_path'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk()->assertJson(['received' => false]);
    }

}
