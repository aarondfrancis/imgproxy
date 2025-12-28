# URL Builder

The `imgproxy()` helper provides a fluent interface for generating image URLs in PHP.

## Basic Usage

```php
use function imgproxy;

// Basic URL
imgproxy('images', 'photo.jpg')
// => /images/photo.jpg

// With width
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
```

## Blade Templates

The builder implements `Stringable` and `Htmlable`, so you can use it directly in Blade:

```blade
<img src="{{ imgproxy('images', 'hero.jpg')->width(800)->webp() }}">

{{-- In srcset --}}
<img
    src="{{ imgproxy('images', 'hero.jpg')->w(800) }}"
    srcset="
        {{ imgproxy('images', 'hero.jpg')->w(400) }} 400w,
        {{ imgproxy('images', 'hero.jpg')->w(800) }} 800w,
        {{ imgproxy('images', 'hero.jpg')->w(1200) }} 1200w
    "
>
```

## Available Methods

### Dimension Methods

| Method | Alias | Description |
|--------|-------|-------------|
| `width(int $width)` | `w()` | Set width in pixels |
| `height(int $height)` | `h()` | Set height in pixels |

```php
imgproxy('images', 'photo.jpg')->width(400)->height(300)
imgproxy('images', 'photo.jpg')->w(400)->h(300)
```

### Quality Method

| Method | Alias | Description |
|--------|-------|-------------|
| `quality(int $quality)` | `q()` | Set quality (1-100) |

```php
imgproxy('images', 'photo.jpg')->quality(80)
imgproxy('images', 'photo.jpg')->q(80)
```

### Format Methods

| Method | Alias | Description |
|--------|-------|-------------|
| `format(string $format)` | `f()` | Set output format |
| `webp()` | | Shortcut for `format('webp')` |
| `png()` | | Shortcut for `format('png')` |
| `jpg()` | | Shortcut for `format('jpg')` |
| `gif()` | | Shortcut for `format('gif')` |

```php
imgproxy('images', 'photo.jpg')->format('webp')
imgproxy('images', 'photo.jpg')->f('webp')
imgproxy('images', 'photo.jpg')->webp()
```

### Fit Methods

| Method | Description |
|--------|-------------|
| `fit(string $mode)` | Set fit mode |
| `cover()` | Shortcut for `fit('cover')` |
| `contain()` | Shortcut for `fit('contain')` |
| `scale()` | Shortcut for `fit('scale')` |
| `scaleDown()` | Shortcut for `fit('scaledown')` |
| `crop()` | Shortcut for `fit('crop')` |

```php
imgproxy('images', 'photo.jpg')->width(400)->height(300)->fit('cover')
imgproxy('images', 'photo.jpg')->width(400)->height(300)->cover()
```

### Cache Busting

| Method | Alias | Description |
|--------|-------|-------------|
| `version(string\|int $v)` | `v()` | Set cache buster value |

```php
imgproxy('images', 'photo.jpg')->width(400)->v(2)
imgproxy('images', 'photo.jpg')->width(400)->version('abc123')
```

### Output

| Method | Description |
|--------|-------------|
| `url()` | Get the URL as a string |
| `__toString()` | Automatic string conversion |
| `toHtml()` | For Blade's Htmlable interface |

```php
$url = imgproxy('images', 'photo.jpg')->width(400)->url();
$url = (string) imgproxy('images', 'photo.jpg')->width(400);
```

## Complete Example

```php
// In a controller or view composer
$heroImage = imgproxy('images', $post->hero_image)
    ->width(1200)
    ->height(630)
    ->cover()
    ->quality(85)
    ->webp()
    ->v($post->updated_at->timestamp);

// In Blade
<meta property="og:image" content="{{ url($heroImage) }}">
<img src="{{ $heroImage }}">
```

## Route Prefix

If you've configured a route prefix, the builder respects it automatically:

```php
// config/imgproxy.php
'route' => [
    'prefix' => 'img',
],

// Result
imgproxy('images', 'photo.jpg')->width(400)
// => /img/w=400/images/photo.jpg
```
