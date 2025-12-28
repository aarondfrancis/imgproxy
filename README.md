# Image Proxy for Laravel

A Laravel package for on-the-fly image resizing and format conversion.

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

| Option | Description | Example |
|--------|-------------|---------|
| `width` | Resize to width (maintains aspect ratio) | `width=400` |
| `height` | Resize to height (maintains aspect ratio) | `height=300` |
| `quality` | JPEG/WebP quality (1-100) | `quality=80` |
| `format` | Output format (jpg, png, gif, webp) | `format=webp` |

### Examples

```html
<!-- Resize to 400px width -->
<img src="/img/width=400/images/photo.jpg">

<!-- Resize and convert to WebP -->
<img src="/img/width=400,format=webp/images/photo.jpg">

<!-- Multiple options -->
<img src="/img/width=800,height=600,quality=85,format=webp/images/photo.jpg">
```

## Configuration

### Sources

Configure image sources in `config/image-proxy.php`:

```php
'sources' => [
    '' => 'public',              // Default: serve from public_path()
    'r2' => 'storage:data',      // Prefix 'r2/' serves from 'data' disk
],
```

Usage with source prefix:

```html
<img src="/img/width=400/r2/images/photo.jpg">
```

### Path Validation

Add custom path validation:

```php
'path_validator' => function (string $source, string $path) {
    // Only allow paths starting with 'images/'
    return str_starts_with($path, 'images/');
},
```

### Restrict Dimensions

Whitelist allowed dimensions to prevent abuse:

```php
'allowed_widths' => [100, 200, 400, 800, 1200],
'allowed_heights' => [100, 200, 400, 800],
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

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12
- Intervention Image 3.x

## License

MIT
