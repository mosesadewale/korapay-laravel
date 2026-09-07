# kora-laravel v3.0.0

This major release requires `mosesadewale/kora-php:^3.0` and inherits its corrected Kora API contracts and decimal-string response amounts.

Laravel 12 and 13 are the supported production targets. Laravel 11 remains installable and CI-tested for legacy applications, but it is security-EOL upstream and contains advisories that cannot be fixed by this adapter.

## Changes

- Added `KORA_CONNECT_TIMEOUT` with a 10-second default.
- Added `KORA_RETRY_UNSAFE_METHODS`, disabled by default. Safe reads may use `KORA_RETRY_ATTEMPTS`; money-moving requests are not retried unless explicitly opted in.
- Hosted checkout examples now use `charges()->checkout()`.
- Amount guidance reflects Kora's JSON Number contract: inputs permit up to two fractional digits, but a JSON number does not preserve trailing-zero formatting.
- Hosted-checkout amount, status, and currency are nullable because Kora omits them from initialization responses. Card responses expose authorization `requiredFields` and `redirectUrl` directly.
- Remittance uses the sandbox-verified merchant API prefix, and unrelated HTTP 409 conflicts are no longer reported as duplicate references.

The adapter intentionally leaves payment payload validation to `kora-php`; Laravel applications should validate their own form and business requirements before invoking it. Validate the coordinated v3 packages in Kora's sandbox before live use.
