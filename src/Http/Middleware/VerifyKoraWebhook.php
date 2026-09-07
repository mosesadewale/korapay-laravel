<?php

declare(strict_types=1);

namespace Kora\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kora\Sdk\Contracts\KoraClientInterface;
use Symfony\Component\HttpFoundation\Response;

final class VerifyKoraWebhook
{
    public function __construct(private readonly KoraClientInterface $kora) {}

    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->headers->get('x-korapay-signature') ?? '';
        $raw       = $request->getContent();

        if (!$this->kora->webhooks()->verify($raw, $signature)) {
            return response()->json(['received' => false]);
        }

        return $next($request);
    }
}
