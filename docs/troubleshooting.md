# Troubleshooting

Common issues and solutions when using ImgProxy.

## Images Not Loading

### 404 Not Found

**Symptom**: Image returns 404 even though the file exists.

**Possible causes**:

1. **Storage link not created**
   ```bash
   php artisan storage:link
   ```

2. **Wrong disk configured** — Ensure the source's `disk` matches your filesystem config:
   ```php
   // config/imgproxy.php
   'sources' => [
       'images' => [
           'disk' => 'public',  // Must match config/filesystems.php
       ],
   ],
   ```

3. **Root path misconfigured** — If using `root`, the path is prepended:
   ```php
   'uploads' => [
       'disk' => 'public',
       'root' => 'uploads',  // /w=400/uploads/photo.jpg loads uploads/photo.jpg
   ],
   ```

4. **Unknown source** — The source name in the URL must exist in config.

### 403 Forbidden

**Symptom**: Image returns 403 Forbidden.

**Possible causes**:

1. **Path validation failing** — Check your validator config:
   ```php
   'validator' => PathValidator::directories(['images']),
   // Only allows paths starting with 'images/'
   ```

2. **Directory traversal blocked** — Paths containing `..` are always blocked.

## Images Not Caching

### CDN not caching responses

**Symptom**: Every request hits your origin server.

**Check for**:

1. **Session middleware** — Remove session-starting middleware from the route:
   ```php
   'route' => [
       'middleware' => [],  // Keep empty or use stateless middleware only
   ],
   ```

2. **Cookie headers** — Any `Set-Cookie` header prevents CDN caching.

3. **Vary header issues** — Some middleware adds `Vary: Cookie` which fragments cache.

### Browser not caching

**Symptom**: Browser refetches on every page load.

**Check**: Open DevTools Network tab and verify `Cache-Control` header is present:
```
Cache-Control: public, max-age=2592000, s-maxage=2592000, immutable
```

## Performance Issues

### Slow initial requests

**This is expected.** The first request for any URL must:
1. Load the source image
2. Process/resize it
3. Encode the output

Subsequent requests should be served from CDN cache.

### Rate limiting in development

**Symptom**: Getting 429 errors during development.

Rate limiting only applies when `APP_ENV=production`. In development, it's disabled. If you're seeing 429s in production, requests are exceeding the limit:

```php
'rate_limit' => [
    'max_attempts' => 10,  // Increase if needed
],
```

## Format Issues

### WebP not working

**Symptom**: WebP images return errors or wrong format.

**Check**:

1. **GD extension** — Ensure GD is compiled with WebP support:
   ```php
   php -r "print_r(gd_info());"
   // Look for: [WebP Support] => 1
   ```

2. **Format allowed** — Check config:
   ```php
   'allowed_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
   ```

### Quality not applying

Quality only affects JPEG and WebP formats. PNG and GIF are lossless and ignore the quality parameter.

## Dimension Issues

### Images not resizing

**Symptom**: Requested dimensions are ignored.

**Possible causes**:

1. **scaledown mode (default)** — Won't enlarge images smaller than requested dimensions. Use `fit=scale` to allow enlarging:
   ```
   /w=1000,fit=scale/images/small.jpg
   ```

2. **Max dimensions exceeded** — Requests above `max_width`/`max_height` return 400:
   ```php
   'max_width' => 2000,
   'max_height' => 2000,
   ```

### Aspect ratio not maintained

When both width and height are specified, use the appropriate fit mode:

- `scaledown` / `scale` — Maintains aspect ratio, fits within dimensions
- `cover` / `crop` — Crops to exact dimensions
- `contain` — Adds padding to maintain aspect ratio at exact dimensions

## Route Conflicts

### ImgProxy route not matching

**Symptom**: Requests go to wrong controller or 404.

**Check route order**. ImgProxy registers its route in the service provider. If you have a catch-all route, it may take precedence.

**Solution**: Register a custom route with explicit constraints:

```php
// config/imgproxy.php
'route' => ['enabled' => false],

// routes/web.php (register early)
Route::get('{options}/{source}/{path}', [ImgProxyController::class, 'show'])
    ->where('options', '([a-zA-Z]+=[a-zA-Z0-9]+,?)+')
    ->whereIn('source', ['images', 'uploads'])  // Explicit sources
    ->where('path', '.+\.[a-zA-Z0-9]+');
```

## Debugging

### Check what path is being resolved

Add temporary logging to see what's happening:

```php
// In a test or tinker
$service = app(\AaronFrancis\ImgProxy\ImgProxyService::class);
$result = $service->resolve('images', 'photo.jpg');
dump($result);
// ['disk' => 'public', 'path' => 'photo.jpg', 'config' => [...]]
```

### Verify file exists on disk

```php
Storage::disk('public')->exists('photo.jpg');
```

### Test URL builder output

```php
imgproxy('images', 'photo.jpg')->width(400)->url();
// "/w=400/images/photo.jpg"
```
