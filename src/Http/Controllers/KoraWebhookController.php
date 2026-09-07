<?php

declare(strict_types=1);

namespace Kora\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kora\Laravel\Events\KoraWebhookReceived;
use Kora\Sdk\Exceptions\WebhookException;
use Kora\Sdk\Contracts\KoraClientInterface;

final class KoraWebhookController extends Controller
{
    public function __construct(private readonly KoraClientInterface $kora) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            $event = $this->kora->webhooks()->parse($request->getContent());
        } catch (WebhookException) {
            return response()->json(['received' => false]);
        }

        event(new KoraWebhookReceived($event));

        return response()->json(['received' => true]);
    }
}
