# Installation

## Requirements

- PHP 8.2 or higher
- Laravel 10, 11, or 12
- GD or Imagick PHP extension

## Install via Composer

```bash
composer require aaronfrancis/imgproxy
```

The package will auto-register its service provider.

## Publish Configuration

Publish the config file to customize sources, cache headers, and other options:

```bash
php artisan vendor:publish --tag=imgproxy-config
```

This creates `config/imgproxy.php`.

## Basic Setup

The default configuration serves images from Laravel's `public` disk:

```php
// config/imgproxy.php
'sources' => [
    'images' => [
        'disk' => 'public',
    ],
],
```

Place images in `storage/app/public/` and ensure the storage link exists:

```bash
php artisan storage:link
```

## Verify Installation

Create a test image at `storage/app/public/test.jpg`, then visit:

```
http://your-app.test/w=200/images/test.jpg
```

You should see a 200px wide version of your image.

## CDN Setup (Recommended)

ImgProxy is designed to work with a CDN. The package sends these cache headers by default:

```
Cache-Control: public, max-age=2592000, s-maxage=2592000, stale-while-revalidate=86400, stale-if-error=86400, immutable
```

### Cloudflare

No additional configuration needed. Cloudflare respects these headers automatically.

### Fastly / Other CDNs

Ensure your CDN is configured to:
- Cache based on the full URL path
- Respect `Cache-Control` headers from origin
- Not add cookies or session headers

## Next Steps

- [Usage](usage.md) — Learn the URL structure and available options
- [Configuration](configuration.md) — Customize sources, limits, and caching
