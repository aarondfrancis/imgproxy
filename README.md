# Image Proxy for Laravel

On-the-fly image resizing and format conversion for Laravel. Transform images via URL parameters—no pre-processing, no storage bloat, just simple URLs that your CDN can cache.

```html
<!-- Original 4000x3000 image -->
<img src="/img/w=800,f=webp/p/photos/hero.jpg">

<!-- Thumbnail with exact dimensions -->
<img src="/img/w=150,h=150,fit=cover/p/photos/avatar.jpg">
```

Upload once, serve any size. Let Cloudflare (or any CDN) cache the results at the edge.

## Installation

```bash
composer require tryhard/image-proxy
```

Publish the config file:

```bash
php artisan vendor:publish --tag=image-proxy-config
```

## Usage

The package automatically registers a route for image proxying:

```
/img/{options}/{path}
```

### Options

Options are comma-separated key=value pairs:

| Option | Alias | Description | Example |
|--------|-------|-------------|---------|
| `width` | `w` | Target width in pixels | `w=400` |
| `height` | `h` | Target height in pixels | `h=300` |
| `fit` | | Resize mode (see below) | `fit=cover` |
| `quality` | `q` | JPEG/WebP quality (1-100) | `q=80` |
| `format` | `f` | Output format (jpg, png, gif, webp) | `f=webp` |
| `v` | | Cache buster (ignored, see below) | `v=2` |

### Fit Modes

| Mode | Description |
|------|-------------|
| `scaledown` | Scale to fit within dimensions, never enlarge (default) |
| `scale` | Scale to fit within dimensions, may enlarge |
| `cover` | Crop to fill exact dimensions (center crop) |
| `contain` | Fit inside dimensions with padding |
| `crop` | Crop from center to exact dimensions |

### Cache Busting

Use the `v` option to bust browser and CDN caches when an image changes. The value is ignored by the proxy but creates a unique URL:

```html
<!-- Original -->
<img src="/img/w=400/p/photos/hero.jpg">

<!-- After updating the image, change v to bust caches -->
<img src="/img/w=400,v=2/p/photos/hero.jpg">
```

Since the URL changes, browsers and CDNs will fetch the new version. Increment `v` each time the source image is updated.

### Examples

```html
<!-- Resize to 400px width -->
<img src="/img/w=400/p/images/photo.jpg">

<!-- Resize and convert to WebP -->
<img src="/img/w=400,f=webp/p/images/photo.jpg">

<!-- Cover crop to exact dimensions -->
<img src="/img/w=800,h=600,fit=cover/p/images/photo.jpg">

<!-- Multiple options -->
<img src="/img/w=800,h=600,q=85,f=webp/p/images/photo.jpg">
```

## Configuration

### Sources

Configure image sources in `config/image-proxy.php`. Each source maps a URL prefix to a filesystem disk. Every source requires an explicit prefix:

```php
'sources' => [
    'p' => 'public',    // /img/w=800/p/path serves from 'public' disk
    'r2' => 'r2',       // /img/w=800/r2/path serves from 'r2' disk
    'media' => 's3',    // /img/w=800/media/path serves from 's3' disk
],
```

Usage:

```html
<!-- Serves from 'public' disk -->
<img src="/img/w=400/p/images/photo.jpg">

<!-- Serves from 'r2' disk -->
<img src="/img/w=400/r2/images/photo.jpg">
```

Requests with an unknown source prefix return 404.

### Path Validation

Restrict which paths can be served using the built-in validators:

```php
use TryHard\ImageProxy\PathValidator;

// Only allow paths in specific directories
'path_validator' => PathValidator::directories('images', 'uploads'),

// Only allow paths matching glob patterns
'path_validator' => PathValidator::matches('images/**/*.jpg', 'photos/*.png'),
```

Pattern syntax:
- `*` matches any characters except `/`
- `**` matches any characters including `/`
- `?` matches a single character

For custom validation, use an invokable class:

```php
'path_validator' => App\ImageProxy\MyValidator::class,
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
    'max_age' => 2592000,  // 30 days
    's_maxage' => 2592000, // CDN cache
    'immutable' => true,
],
```

### Rate Limiting

Rate limiting is enabled by default in production:

```php
'rate_limit' => [
    'enabled' => true,
    'max_attempts' => 10,
    'key_prefix' => 'image-proxy',
],
```

### Route Configuration

Customize the route prefix, middleware, and name:

```php
'route' => [
    'enabled' => true,
    'prefix' => 'img',           // /img/{options}/{path}
    'middleware' => ['web'],
    'name' => 'image-proxy.show',
],
```

Set `prefix` to `null` or `''` to serve from the root:

```php
'prefix' => null,  // /{options}/{path}
```

### Custom Route

Disable the default route and register your own:

```php
// config/image-proxy.php
'route' => [
    'enabled' => false,
],

// routes/web.php
use TryHard\ImageProxy\Http\Controllers\ImageProxyController;

Route::get('images/{options}/{path}', [ImageProxyController::class, 'show'])
    ->where('options', '([a-zA-Z]+=[a-zA-Z0-9]+,?)+')
    ->where('path', '.*\.[a-zA-Z0-9]+')
    ->name('image-proxy.show');
```

## Why No Server-Side Cache?

This package intentionally does not cache processed images on the server. Here's why:

1. **Storage bloat**: Every combination of image + options creates a new file. A single image with 10 width variations, 3 formats, and 5 quality levels = 150 cached files. Multiply by thousands of images and your storage explodes.

2. **CDN is the cache**: The entire point of this architecture is to let your CDN cache the processed images at the edge. Configure your CDN to respect the `Cache-Control` headers (30 days by default) and you get global caching for free.

3. **Simpler invalidation**: When you update an image, just change the `v` parameter. No need to purge server-side caches.

**Recommended setup**: Put Cloudflare, Fastly, or any CDN in front of your app. The first request processes the image, subsequent requests are served from CDN cache.

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12
- Intervention Image 3.x

## License

MIT
