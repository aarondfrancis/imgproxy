# ImgProxy for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aaronfrancis/imgproxy.svg?style=flat-square)](https://packagist.org/packages/aaronfrancis/imgproxy)
[![Total Downloads](https://img.shields.io/packagist/dt/aaronfrancis/imgproxy.svg?style=flat-square)](https://packagist.org/packages/aaronfrancis/imgproxy)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/aarondfrancis/image-proxy/ci.yaml?branch=main&label=tests&style=flat-square)](https://github.com/aarondfrancis/image-proxy/actions?query=workflow%3ACI+branch%3Amain)
[![License](https://img.shields.io/packagist/l/aaronfrancis/imgproxy.svg?style=flat-square)](https://packagist.org/packages/aaronfrancis/imgproxy)

On-the-fly image resizing and format conversion for Laravel. Transform images via URL parameters—no pre-processing, no
cache bloat, just simple URLs that your CDN can cache.

```html
<!-- Original 4000x3000 image -->
<img src="/w=800,f=webp/images/hero.jpg">

<!-- Thumbnail with exact dimensions -->
<img src="/w=150,h=150,fit=cover/images/avatar.jpg">

<!-- Nested paths work too -->
<img src="/w=400/images/photos/2024/january/photo.jpg">
```

Upload once, serve any size. Let Cloudflare (or any CDN) cache the results at the edge.

## Installation

```bash
composer require aaronfrancis/imgproxy
```

Publish the config file:

```bash
php artisan vendor:publish --tag=imgproxy-config
```

## Usage

The package automatically registers a route for image proxying:

```
/{options}/{source}/{path}
```

- `{options}` — comma-separated key=value pairs (e.g., `w=400,f=webp`)
- `{source}` — configured source name (validated against your config)
- `{path}` — path to the image, can include subdirectories

### Options

| Option    | Alias | Description                         | Example     |
|-----------|-------|-------------------------------------|-------------|
| `width`   | `w`   | Target width in pixels              | `w=400`     |
| `height`  | `h`   | Target height in pixels             | `h=300`     |
| `fit`     |       | Resize mode (see below)             | `fit=cover` |
| `quality` | `q`   | JPEG/WebP quality (1-100)           | `q=80`      |
| `format`  | `f`   | Output format (jpg, png, gif, webp) | `f=webp`    |
| `v`       |       | Cache buster (ignored, see below)   | `v=2`       |

### Fit Modes

| Mode        | Description                                             |
|-------------|---------------------------------------------------------|
| `scaledown` | Scale to fit within dimensions, never enlarge (default) |
| `scale`     | Scale to fit within dimensions, may enlarge             |
| `cover`     | Crop to fill exact dimensions (center crop)             |
| `contain`   | Fit inside dimensions with padding                      |
| `crop`      | Crop from center to exact dimensions                    |

### Cache Busting

Use the `v` option to bust browser and CDN caches when an image changes. The value is ignored by the proxy but creates a
unique URL:

```html
<!-- Original -->
<img src="/w=400/images/hero.jpg">

<!-- After updating the image, change v to bust caches -->
<img src="/w=400,v=2/images/hero.jpg">
```

Since the URL changes, browsers and CDNs will fetch the new version. Increment `v` each time the source image is
updated.

### Examples

```html
<!-- Resize to 400px width -->
<img src="/w=400/images/photo.jpg">

<!-- Resize and convert to WebP -->
<img src="/w=400,f=webp/images/photo.jpg">

<!-- Cover crop to exact dimensions -->
<img src="/w=800,h=600,fit=cover/images/photo.jpg">

<!-- Multiple options -->
<img src="/w=800,h=600,q=85,f=webp/images/photo.jpg">

<!-- Different source -->
<img src="/w=400/media/uploads/photo.jpg">
```

## URL Builder

Use the fluent `imgproxy()` helper to generate URLs in your PHP code:

```php
// Basic usage
imgproxy('images', 'photo.jpg')->width(400)
// => /w=400/images/photo.jpg

// Chain multiple options
imgproxy('images', 'photo.jpg')
    ->width(800)
    ->height(600)
    ->fit('cover')
    ->quality(85)
    ->webp()
// => /w=800,h=600,fit=cover,q=85,f=webp/images/photo.jpg

// Cache busting
imgproxy('images', 'photo.jpg')->width(400)->v(2)
// => /w=400,v=2/images/photo.jpg
```

The builder implements `Stringable` and `Htmlable`, so you can use it directly in Blade:

```blade
<img src="{{ imgproxy('images', 'hero.jpg')->width(800)->webp() }}">
```

### Available Methods

| Method | Alias | Description |
|--------|-------|-------------|
| `width(int)` | `w()` | Set width in pixels |
| `height(int)` | `h()` | Set height in pixels |
| `quality(int)` | `q()` | Set quality (1-100) |
| `format(string)` | `f()` | Set output format |
| `fit(string)` | | Set fit mode |
| `version(string)` | `v()` | Cache buster |
| `webp()` | | Shortcut for `format('webp')` |
| `png()` | | Shortcut for `format('png')` |
| `jpg()` | | Shortcut for `format('jpg')` |
| `gif()` | | Shortcut for `format('gif')` |
| `cover()` | | Shortcut for `fit('cover')` |
| `contain()` | | Shortcut for `fit('contain')` |
| `scale()` | | Shortcut for `fit('scale')` |
| `scaleDown()` | | Shortcut for `fit('scaledown')` |
| `crop()` | | Shortcut for `fit('crop')` |
| `url()` | | Get the URL string |

## Configuration

### Sources

Configure image sources in `config/imgproxy.php`. Each source maps a URL prefix to a Laravel filesystem disk:

```php
'sources' => [
    // /w=800/images/photo.jpg serves photo.jpg from 'public' disk
    'images' => [
        'disk' => 'public',
    ],

    // /w=800/uploads/photo.jpg serves from 'public' disk's uploads/ directory
    'uploads' => [
        'disk' => 'public',
        'root' => 'uploads',  // prepended to all paths
    ],

    // /w=800/media/photo.jpg serves from 's3' disk with validation
    'media' => [
        'disk' => 's3',
        'validator' => PathValidator::extensions(['jpg', 'png', 'webp']),
    ],
],
```

Source options:

| Option      | Description                                           |
|-------------|-------------------------------------------------------|
| `disk`      | Laravel filesystem disk name                          |
| `root`      | Directory prepended to all paths (optional)           |
| `validator` | PathValidator or custom class for path validation     |

The `root` option is useful when you want a short URL prefix but files are stored in a subdirectory. For example, with
`'root' => 'uploads'`, a request to `/w=400/media/photo.jpg` loads `uploads/photo.jpg` from the disk.

Unknown sources return 404 at the routing level.

### Path Validation

Restrict which paths can be served using the built-in validators:

```php
use AaronFrancis\ImgProxy\PathValidator;

// Only allow paths in specific directories
PathValidator::directories(['images', 'uploads'])

// Only allow paths matching glob patterns
PathValidator::matches(['**/*.jpg', '**/*.png'])

// Only allow certain extensions
PathValidator::extensions(['jpg', 'png', 'webp'])

// Chain them in any order
PathValidator::directories(['uploads'])->extensions(['jpg', 'png'])
PathValidator::extensions(['jpg', 'png'])->directories(['uploads'])
```

Pattern syntax for `matches()`:

- `*` matches any characters except `/`
- `**` matches any characters including `/`
- `?` matches a single character

The validator receives the full path including the `root` prefix, so you can validate the complete filesystem path.

For custom validation, implement `PathValidatorContract`:

```php
use AaronFrancis\ImgProxy\Contracts\PathValidatorContract;

class MyValidator implements PathValidatorContract
{
    public function validate(string $path): bool
    {
        // Your validation logic
        return true;
    }
}

// In config
'validator' => MyValidator::class,
```

### Maximum Dimensions

Hard caps to prevent abuse:

```php
'max_width' => 2000,
'max_height' => 2000,
```

### Allowed Formats

Restrict which output formats can be requested:

```php
'allowed_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
```

### Default Quality

Set the default quality for JPEG and WebP when not specified in the URL:

```php
'default_quality' => 85,
```

### Cache Headers

Configure the cache headers sent with responses:

```php
'cache' => [
    'max_age' => 2592000,              // 30 days browser cache
    's_maxage' => 2592000,             // 30 days CDN cache
    'stale_while_revalidate' => 86400, // Serve stale for 1 day while revalidating
    'stale_if_error' => 86400,         // Serve stale for 1 day if origin errors
    'immutable' => true,
],
```

- `stale_while_revalidate` — When cache expires, CDN serves stale content while fetching fresh in background
- `stale_if_error` — If origin is unavailable, CDN continues serving stale content instead of errors

### Rate Limiting

Limits requests per IP per image path (prevents abuse by requesting many resize variations). Enabled by default in
production:

```php
'rate_limit' => [
    'enabled' => true,
    'max_attempts' => 10,  // per minute
],
```

Most images should be cached by your CDN, so legitimate users will rarely hit your origin server at all. This limit is a
safety net against bad actors.

### Route Configuration

Customize the route prefix, middleware, and name:

```php
'route' => [
    'enabled' => true,
    // By default this is null, so it serves from root: /{options}/{source}/{path}
    // Add a prefix (e.g. "transform") to serve from a path: /transform/{options}/{source}/{path}
    'prefix' => null,            
    'middleware' => [
        // Ensure your middleware don't start sessions or include cookies! This will prevent caching.
    ],
    'name' => 'imgproxy.show',
],
```

Avoid middleware that starts sessions or sets cookies—these prevent CDN caching.

Set a prefix if you prefer URLs like `/img/{options}/{source}/{path}`:

```php
'prefix' => 'img',
```

### Custom Route

Disable the default route and register your own:

```php
// config/imgproxy.php
'route' => [
    'enabled' => false,
],

// routes/web.php
use AaronFrancis\ImgProxy\Http\Controllers\ImgProxyController;

Route::get('{options}/{source}/{path}', [ImgProxyController::class, 'show'])
    ->where('options', '([a-zA-Z]+=[a-zA-Z0-9]+,?)+')
    ->whereIn('source', array_keys(config('imgproxy.sources')))
    ->where('path', '.+\.[a-zA-Z0-9]+')
    ->name('imgproxy.show');
```

## Why No Server-Side Cache?

This package intentionally does not cache processed images on the server. The entire point of this architecture is to
let your CDN cache the processed images at the edge. Configure your CDN to respect the `Cache-Control` headers (30 days
by default) and you get global caching for free.

**Recommended setup**: Put Cloudflare, Fastly, or any CDN in front of your app. The first request processes the image,
subsequent requests are served from CDN cache.

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12
- Intervention Image 3.x

## License

MIT
