# Usage

## URL Structure

ImgProxy registers a route with this pattern:

```
/{options}/{source}/{path}
```

| Segment | Description | Example |
|---------|-------------|---------|
| `options` | Comma-separated key=value pairs | `w=400,f=webp` |
| `source` | Configured source name | `images` |
| `path` | Path to image (can include subdirectories) | `photos/hero.jpg` |

Example URLs:

```
/w=400/images/photo.jpg
/w=800,h=600,fit=cover/images/hero.jpg
/w=200,f=webp,q=80/media/avatar.png
/w=400/images/photos/2024/january/sunset.jpg
```

## Options

| Option | Alias | Description | Example |
|--------|-------|-------------|---------|
| `width` | `w` | Target width in pixels | `w=400` |
| `height` | `h` | Target height in pixels | `h=300` |
| `fit` | | Resize mode | `fit=cover` |
| `quality` | `q` | JPEG/WebP quality (1-100) | `q=80` |
| `format` | `f` | Output format | `f=webp` |
| `v` | | Cache buster (value ignored) | `v=2` |

### Width & Height

Specify one or both dimensions:

```html
<!-- Width only: height scales proportionally -->
<img src="/w=400/images/photo.jpg">

<!-- Height only: width scales proportionally -->
<img src="/h=300/images/photo.jpg">

<!-- Both: behavior depends on fit mode -->
<img src="/w=400,h=300/images/photo.jpg">
```

### Format

Convert between formats:

```html
<!-- Convert to WebP for smaller file sizes -->
<img src="/w=400,f=webp/images/photo.jpg">

<!-- Convert to PNG for transparency -->
<img src="/f=png/images/logo.jpg">
```

Supported formats: `jpg`, `jpeg`, `png`, `gif`, `webp`

### Quality

Control compression for JPEG and WebP (1-100, default 85):

```html
<!-- High quality -->
<img src="/w=800,q=95/images/photo.jpg">

<!-- Lower quality, smaller file -->
<img src="/w=800,q=60,f=webp/images/photo.jpg">
```

## Fit Modes

When both width and height are specified, the `fit` option controls how the image is resized:

| Mode | Description |
|------|-------------|
| `scaledown` | Scale to fit within dimensions, never enlarge (default) |
| `scale` | Scale to fit within dimensions, may enlarge |
| `cover` | Crop to fill exact dimensions (center crop) |
| `contain` | Fit inside dimensions with padding |
| `crop` | Crop from center to exact dimensions |

### Examples

```html
<!-- scaledown (default): Fits within 400x300, maintains aspect ratio, won't enlarge -->
<img src="/w=400,h=300/images/photo.jpg">
<img src="/w=400,h=300,fit=scaledown/images/photo.jpg">

<!-- cover: Crops to exactly 400x300, centered -->
<img src="/w=400,h=300,fit=cover/images/photo.jpg">

<!-- contain: Fits inside 400x300 with padding -->
<img src="/w=400,h=300,fit=contain/images/photo.jpg">

<!-- scale: Like scaledown but will enlarge small images -->
<img src="/w=400,h=300,fit=scale/images/photo.jpg">

<!-- crop: Crops from center to exact dimensions -->
<img src="/w=400,h=300,fit=crop/images/photo.jpg">
```

## Cache Busting

The `v` parameter creates unique URLs for cache invalidation. The value is ignored by ImgProxy but changes the URL:

```html
<!-- Original -->
<img src="/w=400/images/hero.jpg">

<!-- After updating the source image -->
<img src="/w=400,v=2/images/hero.jpg">

<!-- Use timestamps, hashes, or any value -->
<img src="/w=400,v=abc123/images/hero.jpg">
```

Since the URL changes, browsers and CDNs fetch the new version.

## Common Patterns

### Responsive Images

```html
<img
    src="/w=800/images/hero.jpg"
    srcset="
        /w=400/images/hero.jpg 400w,
        /w=800/images/hero.jpg 800w,
        /w=1200/images/hero.jpg 1200w
    "
    sizes="(max-width: 600px) 400px, (max-width: 1000px) 800px, 1200px"
>
```

### Thumbnails

```html
<!-- Square thumbnails with cover crop -->
<img src="/w=150,h=150,fit=cover/images/avatar.jpg">
```

### WebP with JPEG Fallback

```html
<picture>
    <source srcset="/w=800,f=webp/images/photo.jpg" type="image/webp">
    <img src="/w=800/images/photo.jpg" alt="Photo">
</picture>
```

## Error Responses

ImgProxy returns appropriate HTTP status codes for various error conditions:

| Status | Condition |
|--------|-----------|
| 200 | Success — image processed and returned |
| 400 | Invalid options — width/height exceeds max, invalid quality (not 1-100), invalid format, invalid fit mode |
| 403 | Forbidden — directory traversal attempt (`..` in path) or path validation failed |
| 404 | Not found — unknown source or image file doesn't exist |
| 429 | Too many requests — rate limit exceeded (production only) |

### Examples

```
/w=5000/images/photo.jpg        → 400 (exceeds max_width)
/q=150/images/photo.jpg         → 400 (quality must be 1-100)
/f=bmp/images/photo.jpg         → 400 (format not allowed)
/fit=stretch/images/photo.jpg   → 400 (invalid fit mode)
/w=400/images/../secret.jpg     → 403 (directory traversal)
/w=400/unknown/photo.jpg        → 404 (unknown source)
/w=400/images/missing.jpg       → 404 (file not found)
```

## Next Steps

- [URL Builder](url-builder.md) — Generate URLs programmatically
- [Configuration](configuration.md) — Customize sources and limits
