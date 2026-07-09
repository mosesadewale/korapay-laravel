<?php

declare(strict_types=1);

namespace Kora\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Kora\Laravel\Events\KoraWebhookReceived;
use Kora\Laravel\Tests\TestCase;
use Kora\Sdk\Enums\WebhookEventType;
use PHPUnit\Framework\Attributes\Test;

final class WebhookControllerTest extends TestCase
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

    private function webhook(string $eventType, string $reference = 'ref_001'): TestResponse
    {
        $data    = ['reference' => $reference, 'status' => 'success'];
        $payload = json_encode(['event' => $eventType, 'data' => $data], JSON_THROW_ON_ERROR);
        $sig     = $this->sign($data);

        return $this->call('POST', config('kora.webhook_path'), [], [], [], [
            'HTTP_X_KORAPAY_SIGNATURE' => $sig,
            'CONTENT_TYPE'             => 'application/json',
        ], $payload);
    }

    #[Test]
    public function known_event_dispatches_kora_webhook_received(): void
    {
        Event::fake();

        $this->webhook('charge.success', 'ref_001')->assertStatus(200);

        Event::assertDispatched(KoraWebhookReceived::class, function (KoraWebhookReceived $e): bool {
            return $e->event->type === WebhookEventType::ChargeSuccess->value
                && $e->event->data['reference'] === 'ref_001';
        });
    }

    #[Test]
    public function unknown_event_type_still_dispatches_kora_webhook_received(): void
    {
        Event::fake();

        $this->webhook('some.future.event', 'ref_future')->assertStatus(200);

        Event::assertDispatched(KoraWebhookReceived::class, function (KoraWebhookReceived $e): bool {
            return $e->event->type === 'some.future.event'
                && $e->event->reference === 'ref_future';
        });
    }

    #[Test]
    public function response_body_is_received_true(): void
    {
        $this->webhook('charge.success')->assertJson(['received' => true]);
    }
}
