# kora-laravel

Laravel integration for the [Kora PHP SDK](https://github.com/mosesadewale/kora-php). Provides a service provider, facade, and an optional verified webhook receiver.

## Requirements

- PHP 8.2+
- Laravel 12 or 13; Laravel 11 is available for legacy applications but is security-EOL upstream
- [mosesadewale/kora-php](https://github.com/mosesadewale/kora-php) ^3.0

## Installation

```bash
composer require mosesadewale/kora-laravel
```

The service provider and `Kora` facade are auto-discovered via Laravel's package discovery.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=kora-config
```

Then add to your `.env`:

```env
KORA_SECRET_KEY=sk_live_...
KORA_ENCRYPTION_KEY=...        # required for card payments (32 bytes)
KORA_TIMEOUT=30
KORA_CONNECT_TIMEOUT=10
KORA_RETRY_ATTEMPTS=3
KORA_RETRY_UNSAFE_METHODS=false # keep false unless you have an idempotency plan
```

`KORA_ENVIRONMENT` is optional. When omitted, the package infers the environment from `KORA_SECRET_KEY`.

The adapter forwards payment payloads to `kora-php`. The core SDK accepts positive integer, finite float, or decimal-string amounts with no more than two fractional digits, and validates them before sending. Fields documented by Kora as `Number` are sent as JSON numbers, so JSON may serialize `5000.50` as `5000.5`; the adapter does not promise a literal two-decimal JSON token. Use decimal strings at your application boundary and avoid floating-point arithmetic for ledger calculations.

Full config reference (`config/kora.php`):

```php
return [
    'secret_key'             => env('KORA_SECRET_KEY', ''),
    'encryption_key'         => env('KORA_ENCRYPTION_KEY', ''),
    'environment'            => env('KORA_ENVIRONMENT'),
    'webhook_path'           => env('KORA_WEBHOOK_PATH', 'webhooks/kora'),
    'register_webhook_route' => env('KORA_REGISTER_ROUTE', false),
    'timeout'                => (float) env('KORA_TIMEOUT', 30),
    'connect_timeout'        => (float) env('KORA_CONNECT_TIMEOUT', 10),
    'retry_attempts'         => (int)   env('KORA_RETRY_ATTEMPTS', 3),
    'retry_unsafe_methods'   => filter_var(env('KORA_RETRY_UNSAFE_METHODS', false), FILTER_VALIDATE_BOOLEAN),
];
```

`retry_attempts` defaults to three attempts for safe read methods. `retry_unsafe_methods` defaults to `false`, so money-moving POST requests are not retried automatically. Keep it disabled unless Kora has confirmed an idempotency and reconciliation strategy for your account. An uncertain charge, payout, or refund should be verified by its reference before any manual retry or fulfilment.

If you want to be explicit, set `KORA_ENVIRONMENT=live` or `KORA_ENVIRONMENT=sandbox`. A mismatch with the key prefix throws `InvalidArgumentException` when the lazily registered Kora client is first resolved.

## Usage

Use the `Kora` facade anywhere in your application:

```php
use Illuminate\Support\Str;
use Kora\Laravel\Facades\Kora;

// Initialize a hosted Checkout Redirect charge
$charge = Kora::charges()->checkout([
    'reference'    => 'order_' . Str::uuid(),
    'amount'       => '5000.00',
    'currency'     => 'NGN',
    'customer'     => ['email' => 'user@example.com', 'name' => 'Ada Okonkwo'],
    'redirect_url' => 'https://yourapp.com/callback',
]);

return redirect($charge->checkoutUrl);
```

All resources are available on the facade:

```php
Kora::charges()        // ChargesResource
Kora::mobileMoney()    // MobileMoneyResource
Kora::payouts()        // PayoutsResource
Kora::bulkPayouts()    // BulkPayoutsResource
Kora::balances()       // BalancesResource
Kora::conversions()    // ConversionsResource
Kora::refunds()        // RefundsResource
Kora::poolAccounts()   // PoolAccountsResource
Kora::chargebacks()    // ChargebacksResource
Kora::webhooks()       // WebhookResource
```

See the [kora-php README](https://github.com/mosesadewale/kora-php) for full method signatures and usage examples for each resource.

## Webhooks

Kora signs webhooks with your API `secret_key`.

### Optional route

The package can register `POST webhooks/kora` with signature verification middleware applied, but the route is disabled by default so installing the package does not expose a public endpoint unexpectedly.

Enable it when you want the package-managed receiver:

```env
KORA_REGISTER_ROUTE=true
KORA_WEBHOOK_PATH=webhooks/kora
```

The route is registered outside Laravel's `web` middleware group, so no CSRF exemption is needed.

### Laravel event

When a valid webhook arrives, the controller dispatches one generic event:

```php
use Illuminate\Support\Facades\Event;
use Kora\Laravel\Events\KoraWebhookReceived;
use Kora\Sdk\Enums\WebhookEventType;

Event::listen(KoraWebhookReceived::class, function (KoraWebhookReceived $e) {
    match (WebhookEventType::tryFrom($e->event->type)) {
        WebhookEventType::ChargeSuccess => ProcessSuccessfulCharge::dispatch($e->event->data),
        WebhookEventType::PayoutSuccess => ProcessSuccessfulPayout::dispatch($e->event->data),
        WebhookEventType::RefundSuccess => ProcessSuccessfulRefund::dispatch($e->event->data),
        default => null,
    };
});
```

Your application owns business processing, idempotency, queueing, and event-specific jobs/listeners.

Each `KoraWebhookReceived` event carries a `WebhookEvent $event` property:

```php
$e->event->type;            // raw event string e.g. "charge.success"
$e->event->data;             // array — the full data payload from Kora
$e->event->data['reference'] // the transaction reference
```

Unknown future Kora event strings are still dispatched through `KoraWebhookReceived`; use `$e->event->type` when you need the raw provider value.

### Custom webhook route

If you need full control, disable the built-in route and define your own:

```php
// KORA_REGISTER_ROUTE=false in .env

// routes/api.php
Route::post('webhooks/kora', function (Request $request) {
    $raw       = $request->getContent();
    $signature = $request->headers->get('x-korapay-signature') ?? '';

    if (!Kora::webhooks()->verify($raw, $signature)) {
        return response()->json(['received' => false]);
    }

    $event = Kora::webhooks()->parse($raw);
    // dispatch your own event/job or handle $event manually
    return response()->json(['received' => true]);
});
```

The built-in receiver acknowledges invalid signatures and malformed payloads with HTTP 200 and `received: false`, without dispatching an event. This prevents repeated provider delivery attempts while ensuring untrusted payloads are ignored; monitor these responses in your application logs.

## Error handling

```php
use Kora\Sdk\Exceptions\ApiException;
use Kora\Sdk\Exceptions\AuthenticationException;
use Kora\Sdk\Exceptions\DuplicateReferenceException;
use Kora\Sdk\Exceptions\InsufficientFundsException;
use Kora\Sdk\Exceptions\KoraException;
use Kora\Sdk\Exceptions\NetworkException;
use Kora\Sdk\Exceptions\ValidationException;

try {
    Kora::payouts()->disburse($payload);
} catch (DuplicateReferenceException $e) {
    // reference already used
} catch (InsufficientFundsException $e) {
    // wallet balance too low
} catch (ValidationException $e) {
    Log::warning('Kora validation', $e->errors());
} catch (ApiException $e) {
    Log::error('Kora server error', ['context' => $e->context()]);
} catch (KoraException $e) {
    Log::error($e->getMessage());
}
```

## Testing

Keep Kora behind an application-owned payment interface and mock that narrow
boundary in domain tests. SDK resource classes are final and are not mock seams.
For SDK transport contract tests, inject a recording `HttpClientInterface`
through the core SDK so no network calls or real credentials are involved.

```php
use Kora\Sdk\DTOs\ChargeResponse;

$charge = ChargeResponse::fromArray([
    'reference'    => 'ref_001',
    'status'       => 'pending',
    'amount'       => '5000.00',
    'currency'     => 'NGN',
    'checkout_url' => 'https://pay.korahq.com/checkout/ref_001',
]);

// Return this value from your application-owned payment gateway fake.
```

For webhook controller tests, use `Event::fake()` and post a signed payload:

```php
use Illuminate\Support\Facades\Event;
use Kora\Laravel\Events\KoraWebhookReceived;

Event::fake();

$secret  = config('kora.secret_key');
$data    = ['reference' => 'ref_001', 'status' => 'success'];
$payload = json_encode(['event' => 'charge.success', 'data' => $data]);
$sig     = hash_hmac('sha256', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $secret);

$this->call('POST', config('kora.webhook_path'), [], [], [], [
    'HTTP_X_KORAPAY_SIGNATURE' => $sig,
    'CONTENT_TYPE'             => 'application/json',
], $payload)->assertStatus(200);

Event::assertDispatched(KoraWebhookReceived::class, function (KoraWebhookReceived $e) {
    return $e->event->type === 'charge.success'
        && $e->event->data['reference'] === 'ref_001';
});
```

## Laravel

This package is the Laravel integration. For framework-agnostic usage see [mosesadewale/kora-php](https://github.com/mosesadewale/kora-php).

## License

MIT
