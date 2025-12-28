# Introduction

ImgProxy is a Laravel package for on-the-fly image resizing and format conversion. Transform images via URL parameters—no pre-processing, no cache bloat, just simple URLs that your CDN can cache.

## Why ImgProxy?

Traditional image handling in Laravel typically involves one of two approaches:

1. **Pre-generate all sizes** — Upload an image and immediately create every size variation you might need. This bloats storage and requires re-processing when design requirements change.

2. **Process on first request, cache locally** — Generate sizes on-demand but store them on disk. This still creates storage bloat and adds complexity around cache invalidation.

ImgProxy takes a different approach: **process on-demand, cache at the CDN**.

```html
<!-- Original 4000x3000 image -->
<img src="/w=800,f=webp/images/hero.jpg">

<!-- Thumbnail with exact dimensions -->
<img src="/w=150,h=150,fit=cover/images/avatar.jpg">
```

Upload once, serve any size. The first request processes the image; subsequent requests are served from your CDN's edge cache worldwide.

## How It Works

1. A request comes in for `/w=400,f=webp/images/photo.jpg`
2. ImgProxy parses the options (`width=400`, `format=webp`)
3. Loads `photo.jpg` from the configured `images` source
4. Resizes and converts the image
5. Returns it with long-lived cache headers

Your CDN caches the response. Future requests for the same URL are served instantly from the edge—your Laravel app never sees them.

## Key Features

- **URL-based transformations** — Width, height, quality, format, and fit mode via simple URL parameters
- **Multiple sources** — Serve from different filesystem disks (local, S3, etc.)
- **Path validation** — Restrict which files can be served with built-in or custom validators
- **CDN-optimized caching** — Aggressive cache headers with `stale-while-revalidate` support
- **Rate limiting** — Protect against abuse from requesting many variations
- **Fluent URL builder** — Generate URLs programmatically with the `imgproxy()` helper

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12
- Intervention Image 3.x (installed automatically)

## Quick Example

```php
// In your Blade template
<img src="{{ imgproxy('images', 'hero.jpg')->width(800)->webp() }}">

// Or use raw URLs
<img src="/w=800,f=webp/images/hero.jpg">
```

Both produce: `/w=800,f=webp/images/hero.jpg`

Ready to get started? Head to [Installation](installation.md).
