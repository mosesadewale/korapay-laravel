<?php

declare(strict_types=1);

namespace Kora\Laravel\Tests\Feature;

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
    public function invalid_signature_returns_401(): void
    {
        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'ref_001']], JSON_THROW_ON_ERROR);

        $response = $this->call('POST', config('kora.webhook_path'), [], [], [], [
            'HTTP_X_KORAPAY_SIGNATURE' => 'bad_signature',
            'CONTENT_TYPE'             => 'application/json',
        ], $payload);

        $response->assertStatus(401);
    }

    #[Test]
    public function route_is_not_subject_to_csrf(): void
    {
        // 401 = reached VerifyKoraWebhook; 419 would mean CSRF fired first
        $response = $this->call('POST', config('kora.webhook_path'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{}');

        $response->assertStatus(401);
    }

    #[Test]
    public function missing_signature_returns_401(): void
    {
        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'ref_001']], JSON_THROW_ON_ERROR);

        $response = $this->call('POST', config('kora.webhook_path'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(401);
    }

}
