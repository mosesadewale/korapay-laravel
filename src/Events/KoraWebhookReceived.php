<?php

declare(strict_types=1);

namespace Kora\Laravel\Events;

use Kora\Sdk\DTOs\WebhookEvent;

final readonly class KoraWebhookReceived
{
    public function __construct(public WebhookEvent $event) {}
}
