# Oh Dear Health Check for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devskio/laravel-ohdear-health-check.svg?style=flat-square)](https://packagist.org/packages/devskio/laravel-ohdear-health-check)
[![Total Downloads](https://img.shields.io/packagist/dt/devskio/laravel-ohdear-health-check.svg?style=flat-square)](https://packagist.org/packages/devskio/laravel-ohdear-health-check)
[![PHP Version](https://img.shields.io/packagist/php-v/devskio/laravel-ohdear-health-check.svg?style=flat-square)](https://packagist.org/packages/devskio/laravel-ohdear-health-check)
[![License](https://img.shields.io/packagist/l/devskio/laravel-ohdear-health-check.svg?style=flat-square)](LICENSE.md)

A Laravel package that exposes a health-check HTTP endpoint compatible with [Oh Dear](https://ohdear.app).  
It ships with three built-in checks (database, disk space, error log) and is fully configurable via environment variables — no config file publish required.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.2` |
| Laravel | `^10 \| ^11 \| ^12` |
| spatie/laravel-health | `^1.23` |
| ohdearapp/health-check-results | `^1.0` |

---

## Installation

```bash
composer require devskio/laravel-ohdear-health-check
```

The service provider is auto-discovered — no manual registration needed.

### Publish config (optional)

```bash
php artisan vendor:publish --tag="ohdear-health-check-config"
```

---

## Usage

### Health endpoint

After installation the health check endpoint is available at `/healthcheck` by default.

It runs the configured checks and returns an aggregated JSON payload:

```json
{
  "status": "ok",
  "finished_at": "2026-07-10T12:00:00+00:00",
  "checks": [
    {
      "name": "DatabaseCheck",
      "label": "DatabaseCheck",
      "status": "ok",
      "notification_message": null,
      "short_summary": null,
      "meta": []
    }
  ]
}
```

### Secret protection (recommended)

Oh Dear supports sending a shared secret with every request. Set the secret in your `.env`:

```dotenv
OHDEAR_HEALTH_CHECK_SECRET=your-secret
```

Oh Dear will include it as a request header. You can also test it manually:

```bash
# via header (Oh Dear's native header name)
curl -H "oh-dear-health-check-secret: your-secret" https://your-app.com/healthcheck

# via query param
curl "https://your-app.com/healthcheck?secret=your-secret"
```

If the secret is missing or wrong the endpoint returns HTTP `403`.

---

## Configuration via environment variables

You can control every aspect of the package through environment variables without ever touching the config file.

### Route

| Variable | Default | Description |
|---|---|---|
| `OHDEAR_HEALTH_CHECK_PATH` | `/healthcheck` | URL path of the health endpoint |
| `OHDEAR_HEALTH_CHECK_MIDDLEWARE` | `web` | Comma-separated middleware list, e.g. `web,throttle:60,1` |
| `OHDEAR_HEALTH_CHECK_SECRET` | _(empty)_ | Shared secret; leave empty to disable authentication |

### Response format

| Variable | Default | Description |
|---|---|---|
| `OHDEAR_HEALTH_CHECK_RESPONSE_FORMAT` | `array` | `array` (package-native) or `ohdear` (raw Oh Dear JSON) |

### Enable / disable checks

| Variable | Default | Description |
|---|---|---|
| `OHDEAR_HEALTH_CHECK_DB_ENABLED` | `true` | Enable/disable the database check |
| `OHDEAR_HEALTH_CHECK_DISK_ENABLED` | `true` | Enable/disable the disk space check |
| `OHDEAR_HEALTH_CHECK_ERROR_LOG_ENABLED` | `true` | Enable/disable the error log check |

### Thresholds

| Variable | Default | Description |
|---|---|---|
| `OHDEAR_HEALTH_CHECK_DISK_PATH` | `/` | Filesystem path to check |
| `OHDEAR_HEALTH_CHECK_DISK_WARN_PCT` | `70` | Warn when disk usage exceeds this % |
| `OHDEAR_HEALTH_CHECK_DISK_FAIL_PCT` | `90` | Fail when disk usage exceeds this % |
| `OHDEAR_HEALTH_CHECK_ERROR_LOG_WARN_MB` | _(empty)_ | Warn when log file exceeds this size (MB) |
| `OHDEAR_HEALTH_CHECK_ERROR_LOG_MAX_MB` | `50` | Fail when log file exceeds this size (MB) |

---

## Built-in checks

### DatabaseCheck

Verifies the default database connection via PDO. Returns `ok` on success, `failed` on error.

### UsedDiskSpaceCheck

Reports disk-usage percentage for the configured path.

- Returns `warning` when used space exceeds `OHDEAR_HEALTH_CHECK_DISK_WARN_PCT`
- Returns `failed` when used space exceeds `OHDEAR_HEALTH_CHECK_DISK_FAIL_PCT`

### ErrorLogCheck

Reads the size of the active Laravel log file (supports `single`, `daily`, and `stack` channel drivers).

- Returns `warning` when log size exceeds `OHDEAR_HEALTH_CHECK_ERROR_LOG_WARN_MB`
- Returns `failed` when log size exceeds `OHDEAR_HEALTH_CHECK_ERROR_LOG_MAX_MB`

---

## Adding custom checks

### Via config

Publish the config file and append to `additional_checks`:

```php
// config/ohdear-health-check.php
'additional_checks' => [
    \App\HealthChecks\RedisCheck::class,
    [
        'class'   => \App\HealthChecks\QueueCheck::class,
        'options' => ['max_queue_size' => 1000],
    ],
],
```

Custom checks must extend `Spatie\Health\Checks\Check` and implement `run(): Result`.

### Via env-only mode

Set `OHDEAR_HEALTH_CHECK_USE_ENV_ONLY=true` to build the checks list exclusively from the environment flags above, ignoring any config-file values.

---

## Response formats

### `array` (default)

Package-native format — always returns HTTP `200`:

```json
{
  "status": "ok | warning | failed",
  "finished_at": "<ISO 8601>",
  "checks": [ { "name": "...", "label": "...", "status": "...", ... } ]
}
```

### `ohdear`

Raw Oh Dear compatible JSON, produced directly by [`ohdearapp/health-check-results`](https://github.com/ohdearapp/health-check-results):

```dotenv
OHDEAR_HEALTH_CHECK_RESPONSE_FORMAT=ohdear
```

---

## Local development / smoke-testing

```bash
# 1. Add this package as a local path repository in your test app
composer config repositories.local path "/absolute/path/to/laravel-ohdear-health-check"

# 2. Require it
composer require devskio/laravel-ohdear-health-check:@dev

# 3. Start the server and hit the endpoint
php artisan serve
curl -i http://127.0.0.1:8000/healthcheck
```

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## Credits

- **[Devsk.io](https://devsk.io)** — development company
- All [contributors](https://github.com/devskio/laravel-ohdear-health-check/contributors)

---

## License

The MIT License (MIT). Copyright © 2026 [Devsk.io](https://devsk.io). Please see [License File](LICENSE.md) for more information.
