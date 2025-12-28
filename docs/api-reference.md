# API Reference

Complete reference for ImgProxy's public classes and methods.

## Helper Function

### imgproxy()

```php
function imgproxy(string $source, string $path): UrlBuilder
```

Creates a fluent URL builder for generating image proxy URLs.

**Parameters**:
- `$source` — Configured source name (must exist in `config('imgproxy.sources')`)
- `$path` — Path to the image file

**Returns**: `UrlBuilder` instance

**Example**:
```php
imgproxy('images', 'photo.jpg')->width(400)->webp();
```

---

## UrlBuilder

`AaronFrancis\ImgProxy\UrlBuilder`

Fluent builder for constructing image proxy URLs. Implements `Stringable` and `Htmlable`.

### Constructor

```php
public function __construct(string $source, string $path)
```

### Dimension Methods

```php
public function width(int $width): static
public function w(int $width): static      // Alias

public function height(int $height): static
public function h(int $height): static     // Alias
```

### Quality Method

```php
public function quality(int $quality): static  // 1-100
public function q(int $quality): static        // Alias
```

### Format Methods

```php
public function format(string $format): static
public function f(string $format): static      // Alias

// Shortcuts
public function webp(): static
public function png(): static
public function jpg(): static
public function gif(): static
```

### Fit Methods

```php
public function fit(string $mode): static

// Shortcuts
public function cover(): static      // fit('cover')
public function contain(): static    // fit('contain')
public function scale(): static      // fit('scale')
public function scaleDown(): static  // fit('scaledown')
public function crop(): static       // fit('crop')
```

### Cache Busting

```php
public function version(string|int $version): static
public function v(string|int $version): static  // Alias
```

### Output Methods

```php
public function url(): string
public function toHtml(): string      // Htmlable interface
public function __toString(): string  // Stringable interface
```

---

## ImgProxyService

`AaronFrancis\ImgProxy\ImgProxyService`

Core service for resolving sources and loading images. Registered as a singleton.

### resolve()

```php
public function resolve(string $source, string $path): array
```

Resolves a source and path to disk information.

**Parameters**:
- `$source` — Source name from config
- `$path` — Image path

**Returns**:
```php
[
    'disk' => string,    // Laravel filesystem disk name
    'path' => string,    // Full path including root prefix
    'config' => array,   // Source configuration
]
```

**Throws**:
- `HttpException` (404) — Unknown source
- `HttpException` (403) — Directory traversal or validation failure

### loadImage()

```php
public function loadImage(string $disk, string $path): string
```

Loads raw image data from a filesystem disk.

**Parameters**:
- `$disk` — Laravel filesystem disk name
- `$path` — Path to image file

**Returns**: Raw image data as string

**Throws**:
- `NotFoundHttpException` (404) — File not found

---

## PathValidator

`AaronFrancis\ImgProxy\PathValidator`

Static factory for creating path validators.

### Static Methods

```php
public static function directories(array $directories): PathValidatorBuilder
public static function extensions(array $extensions): PathValidatorBuilder
public static function matches(array $patterns): PathValidatorBuilder
```

All methods return a `PathValidatorBuilder` for chaining.

**Example**:
```php
PathValidator::directories(['uploads'])
    ->extensions(['jpg', 'png'])
    ->matches(['**/*']);
```

---

## PathValidatorBuilder

`AaronFrancis\ImgProxy\PathValidatorBuilder`

Implements `PathValidatorContract`. Chainable validator with multiple constraint types.

### validate()

```php
public function validate(string $path): bool
```

Validates a path against all configured constraints. Returns `true` only if **all** constraints pass.

### directories()

```php
public function directories(array $directories): static
```

Allows paths starting with any of the given directories.

### extensions()

```php
public function extensions(array $extensions): static
```

Allows paths with any of the given file extensions (case-insensitive).

### matches()

```php
public function matches(array $patterns): static
```

Allows paths matching any of the given glob patterns.

**Pattern syntax**:
- `*` — Matches any characters except `/`
- `**` — Matches any characters including `/`
- `?` — Matches a single character

---

## PathValidatorContract

`AaronFrancis\ImgProxy\Contracts\PathValidatorContract`

Interface for custom path validators.

```php
interface PathValidatorContract
{
    public function validate(string $path): bool;
}
```

Implement this interface to create custom validation logic:

```php
class MyValidator implements PathValidatorContract
{
    public function validate(string $path): bool
    {
        return str_starts_with($path, 'allowed/');
    }
}
```

---

## ImgProxyController

`AaronFrancis\ImgProxy\Http\Controllers\ImgProxyController`

Handles HTTP requests for image processing.

### show()

```php
public function show(
    Request $request,
    string $options,
    string $source,
    string $path
): Response
```

Processes and returns a transformed image.

**Parameters**:
- `$request` — Laravel HTTP request
- `$options` — Comma-separated options string (e.g., `w=400,f=webp`)
- `$source` — Source name
- `$path` — Image path

**Returns**: Image response with appropriate headers

**Response Headers**:
- `Content-Type` — Image MIME type
- `Cache-Control` — Configured cache directives

---

## ImgProxyServiceProvider

`AaronFrancis\ImgProxy\ImgProxyServiceProvider`

Laravel service provider. Auto-discovered via composer.

### Registered Bindings

- `ImgProxyService` — Singleton

### Published Assets

```bash
php artisan vendor:publish --tag=imgproxy-config
```

Publishes `config/imgproxy.php`.

---

## Configuration Schema

Full configuration options in `config/imgproxy.php`:

```php
return [
    // Route registration
    'route' => [
        'enabled' => true,           // bool
        'prefix' => null,            // string|null
        'middleware' => [],          // array
        'name' => 'imgproxy.show',   // string
    ],

    // Image sources
    'sources' => [
        'source_name' => [
            'disk' => 'public',      // string (required)
            'root' => null,          // string|null
            'validator' => null,     // PathValidatorContract|callable|string|null
        ],
    ],

    // Rate limiting
    'rate_limit' => [
        'enabled' => true,           // bool
        'max_attempts' => 10,        // int (per minute per IP per path)
    ],

    // Image processing
    'default_quality' => 85,         // int (1-100)
    'max_width' => 2000,             // int (pixels)
    'max_height' => 2000,            // int (pixels)
    'allowed_formats' => [           // array
        'jpg', 'jpeg', 'png', 'gif', 'webp'
    ],

    // Cache headers
    'cache' => [
        'max_age' => 2592000,              // int (seconds)
        's_maxage' => 2592000,             // int (seconds)
        'stale_while_revalidate' => 86400, // int (seconds)
        'stale_if_error' => 86400,         // int (seconds)
        'immutable' => true,               // bool
    ],
];
```
