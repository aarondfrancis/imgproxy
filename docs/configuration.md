# Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=imgproxy-config
```

This creates `config/imgproxy.php` with all available options.

## Sources

Define where images can be loaded from. Each source maps a URL prefix to a Laravel filesystem disk:

```php
'sources' => [
    // /w=800/images/photo.jpg serves from 'public' disk
    'images' => [
        'disk' => 'public',
    ],

    // /w=800/uploads/photo.jpg serves from uploads/ subdirectory
    'uploads' => [
        'disk' => 'public',
        'root' => 'uploads',
    ],

    // /w=800/media/photo.jpg serves from S3 with validation
    'media' => [
        'disk' => 's3',
        'validator' => PathValidator::extensions(['jpg', 'png', 'webp']),
    ],
],
```

### Source Options

| Option | Type | Description |
|--------|------|-------------|
| `disk` | string | Laravel filesystem disk name |
| `root` | string | Directory prepended to all paths (optional) |
| `validator` | PathValidator\|class | Path validator (optional) |

### Root Directory

The `root` option prepends a directory to all paths for that source:

```php
'uploads' => [
    'disk' => 'public',
    'root' => 'user-uploads',
],
```

Request: `/w=400/uploads/photo.jpg`
Loads from: `user-uploads/photo.jpg` on the `public` disk

### Unknown Sources

Requests with an unknown source return 404 at the routing level.

## Maximum Dimensions

Prevent abuse by capping requested dimensions:

```php
'max_width' => 2000,
'max_height' => 2000,
```

Requests exceeding these limits return 400 Bad Request.

## Allowed Formats

Restrict which output formats can be requested:

```php
'allowed_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
```

Requests for unlisted formats return 400 Bad Request.

## Default Quality

JPEG and WebP quality when not specified in the URL:

```php
'default_quality' => 85,
```

## Cache Headers

Control how browsers and CDNs cache responses:

```php
'cache' => [
    'max_age' => 2592000,              // 30 days - browser cache
    's_maxage' => 2592000,             // 30 days - CDN cache
    'stale_while_revalidate' => 86400, // 1 day - serve stale while fetching
    'stale_if_error' => 86400,         // 1 day - serve stale on origin error
    'immutable' => true,               // content won't change at this URL
],
```

### Cache Header Options

| Option | Default | Description |
|--------|---------|-------------|
| `max_age` | 2592000 | Browser cache duration in seconds |
| `s_maxage` | 2592000 | CDN/proxy cache duration in seconds |
| `stale_while_revalidate` | 86400 | Grace period to serve stale while revalidating |
| `stale_if_error` | 86400 | Grace period to serve stale if origin errors |
| `immutable` | true | Indicates content won't change |

The resulting header:
```
Cache-Control: public, max-age=2592000, s-maxage=2592000, stale-while-revalidate=86400, stale-if-error=86400, immutable
```

## Rate Limiting

Protect against abuse by limiting requests per IP per image path:

```php
'rate_limit' => [
    'enabled' => true,
    'max_attempts' => 10,  // per minute per path
],
```

Rate limiting only applies in production (`APP_ENV=production`). This prevents bad actors from requesting many size variations of the same image to exhaust server resources.

Legitimate users rarely hit your origin since most requests are served from CDN cache.

## Route Configuration

Customize the route registration:

```php
'route' => [
    'enabled' => true,
    'prefix' => null,
    'middleware' => [],
    'name' => 'imgproxy.show',
],
```

### Route Options

| Option | Default | Description |
|--------|---------|-------------|
| `enabled` | true | Whether to register the default route |
| `prefix` | null | URL prefix (e.g., `'img'` for `/img/{options}/{source}/{path}`) |
| `middleware` | [] | Middleware to apply |
| `name` | 'imgproxy.show' | Route name |

### Adding a Prefix

```php
'prefix' => 'img',
```

URLs become: `/img/w=400/images/photo.jpg`

### Middleware Warning

Avoid middleware that:
- Starts sessions
- Sets cookies
- Adds CSRF tokens

These prevent CDN caching and add unnecessary overhead.

## Custom Route

For full control, disable the default route and register your own:

```php
// config/imgproxy.php
'route' => [
    'enabled' => false,
],
```

```php
// routes/web.php
use AaronFrancis\ImgProxy\Http\Controllers\ImgProxyController;

Route::get('{options}/{source}/{path}', [ImgProxyController::class, 'show'])
    ->where('options', '([a-zA-Z]+=[a-zA-Z0-9]+,?)+')
    ->whereIn('source', array_keys(config('imgproxy.sources')))
    ->where('path', '.+\.[a-zA-Z0-9]+')
    ->name('imgproxy.show');
```

## Full Example Configuration

```php
<?php

use AaronFrancis\ImgProxy\PathValidator;

return [
    'route' => [
        'enabled' => true,
        'prefix' => null,
        'middleware' => [],
        'name' => 'imgproxy.show',
    ],

    'sources' => [
        'images' => [
            'disk' => 'public',
        ],
        'uploads' => [
            'disk' => 'public',
            'root' => 'uploads',
            'validator' => PathValidator::extensions(['jpg', 'png', 'webp']),
        ],
        's3' => [
            'disk' => 's3',
            'validator' => PathValidator::directories(['images', 'media']),
        ],
    ],

    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 10,
    ],

    'default_quality' => 85,

    'cache' => [
        'max_age' => 2592000,
        's_maxage' => 2592000,
        'stale_while_revalidate' => 86400,
        'stale_if_error' => 86400,
        'immutable' => true,
    ],

    'max_width' => 2000,
    'max_height' => 2000,

    'allowed_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
];
```
