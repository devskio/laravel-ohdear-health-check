# Oh Dear Health Check for Laravel

## Installation

You can install the package via composer:

```bash
composer require devskio/laravel-ohdear-health-check
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag="ohdear-health-check-config"
```

## Usage

### Health endpoint

After installation, the health check endpoint is available at `/healthcheck` by default.

It will run the configured checks (by default: database, disk usage, error log size) and returns an aggregated JSON payload with check results.

You can change the path/middleware in `config/ohdear-health-check.php` or entirely via environment variables.

### Secret protection (recommended)

To protect the health endpoint with a shared secret, set:

```bash
OHDEAR_HEALTH_CHECK_SECRET=your-secret
```

Then you must provide it either as:

- header: `X-OhDear-HealthCheck-Secret: your-secret`
- or query: `/healthcheck?secret=your-secret`

If it doesn’t match, the endpoint returns HTTP `403`.

### Configure via environment variables

You can control the route and which checks run without touching/publishing the config file.

#### Mode

- `OHDEAR_HEALTH_CHECK_USE_ENV_ONLY` (default: `false`)
  - `true`: build the checks list only from the env flags below.
  - `false`: use package defaults, but still allow env overrides.

#### Route

- `OHDEAR_HEALTH_CHECK_PATH` (default: `/healthcheck`)
- `OHDEAR_HEALTH_CHECK_MIDDLEWARE` (default: `web`)
  - comma-separated list, e.g. `web,throttle:60,1`

#### Enable/disable checks

- `OHDEAR_HEALTH_CHECK_DB_ENABLED` (default: `true`)
- `OHDEAR_HEALTH_CHECK_DISK_ENABLED` (default: `true`)
- `OHDEAR_HEALTH_CHECK_ERROR_LOG_ENABLED` (default: `true`)

#### Thresholds

- `OHDEAR_HEALTH_CHECK_DISK_WARN_PCT` (default: `70`)
- `OHDEAR_HEALTH_CHECK_DISK_FAIL_PCT` (default: `90`)
- `OHDEAR_HEALTH_CHECK_ERROR_LOG_WARN_MB` (optional warning threshold)
- `OHDEAR_HEALTH_CHECK_ERROR_LOG_MAX_MB` (default: `50`)

### Advanced integrations

You can extend the package by appending integration-specific checks through the `ohdear-health-check.additional_checks` config key.

If you need the raw Oh Dear JSON payload instead of the package array payload, set:

```bash
OHDEAR_HEALTH_CHECK_RESPONSE_FORMAT=ohdear
```

### Smoke-testing in a Laravel app

In a Laravel app, add the package as a path repo and require it:

```bash
composer config repositories.local path "/absolute/path/to/laravel-ohdear-health-check"
composer require devskio/laravel-ohdear-health-check:@dev
php artisan serve
```

Then hit:

```bash
curl -i http://127.0.0.1:8000/healthcheck
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
