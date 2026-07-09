# kora-laravel v2.0.0

## Breaking Changes

- Removed `KORA_WEBHOOK_SECRET` / `webhook_secret` configuration.
- Webhook verification now always uses `KORA_SECRET_KEY`.
- The package now requires `mosesadewale/kora-php:^2.0`.

## Improvements

- `KORA_ENVIRONMENT` is optional and inferred from the secret key prefix.

## Migration

- Remove `KORA_WEBHOOK_SECRET` from your environment and config.
- Ensure webhook verification uses the same `KORA_SECRET_KEY` as API requests.
- Update your dependency to `mosesadewale/kora-php:^2.0`.
