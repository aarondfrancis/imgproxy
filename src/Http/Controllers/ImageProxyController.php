<?php

namespace TryHard\ImageProxy\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class ImageProxyController extends Controller
{
    public function show(Request $request, string $options, string $path): Response
    {
        if ($this->shouldRateLimit()) {
            $this->rateLimit($request, $path);
        }

        [$source, $resolvedPath] = $this->resolveSource($path);

        $this->validatePath($source, $resolvedPath);

        $imageData = $this->loadImage($source, $resolvedPath);
        $extension = pathinfo($resolvedPath, PATHINFO_EXTENSION);

        $image = Image::read($imageData);
        $parsedOptions = $this->parseOptions($options);

        $this->validateOptions($parsedOptions);

        if (Arr::hasAny($parsedOptions, ['width', 'height'])) {
            $width = $parsedOptions['width'] ?? null;
            $height = $parsedOptions['height'] ?? null;

            $image->scaleDown(width: $width, height: $height);
        }

        $quality = (int) Arr::get($parsedOptions, 'quality', config('image-proxy.default_quality', 85));
        $format = Arr::get($parsedOptions, 'format', $extension);

        $encoder = match (strtolower($format)) {
            'png' => new PngEncoder,
            'gif' => new GifEncoder,
            'webp' => new WebpEncoder(quality: $quality),
            default => new JpegEncoder(quality: $quality),
        };

        $encoded = $encoder->encode($image);

        return response($encoded, 200)
            ->header('Content-Type', $encoded->mimetype())
            ->header('Cache-Control', $this->buildCacheControl());
    }

    protected function shouldRateLimit(): bool
    {
        return App::isProduction() && config('image-proxy.rate_limit.enabled', true);
    }

    protected function rateLimit(Request $request, string $path): void
    {
        $prefix = config('image-proxy.rate_limit.key_prefix', 'image-proxy');
        $maxAttempts = config('image-proxy.rate_limit.max_attempts', 10);

        $allowed = RateLimiter::attempt(
            key: $prefix . ':' . $request->ip() . ':' . $path,
            maxAttempts: $maxAttempts,
            callback: fn () => true
        );

        if (!$allowed) {
            throw new HttpResponseException(Redirect::to($path));
        }
    }

    protected function resolveSource(string $path): array
    {
        $sources = config('image-proxy.sources', ['' => 'public']);

        foreach ($sources as $prefix => $resolver) {
            if ($prefix === '') {
                continue;
            }

            if (Str::startsWith($path, $prefix . '/')) {
                $resolvedPath = Str::after($path, $prefix . '/');

                return [$resolver, $resolvedPath];
            }
        }

        return [$sources[''] ?? 'public', $path];
    }

    protected function validatePath(string $source, string $path): void
    {
        $validator = config('image-proxy.path_validator');

        if ($validator && is_callable($validator)) {
            abort_unless($validator($source, $path), 403);
        }

        // Always prevent directory traversal
        abort_if(str_contains($path, '..'), 403);
    }

    protected function loadImage(string $source, string $path): string
    {
        if ($source === 'public') {
            $fullPath = public_path($path);
            abort_unless(File::exists($fullPath), 404);

            return File::get($fullPath);
        }

        if (Str::startsWith($source, 'storage:')) {
            $disk = Str::after($source, 'storage:');
            abort_unless(Storage::disk($disk)->exists($path), 404);

            return Storage::disk($disk)->get($path);
        }

        // Custom resolver (callable)
        if (is_callable($source)) {
            return $source($path);
        }

        abort(500, 'Invalid image source configuration');
    }

    protected function parseOptions(string $options): array
    {
        return Collection::explode(',', $options)
            ->mapWithKeys(function ($opt) {
                $parts = explode('=', $opt, 2);

                return [$parts[0] => $parts[1] ?? null];
            })
            ->toArray();
    }

    protected function validateOptions(array $options): void
    {
        $allowedWidths = config('image-proxy.allowed_widths');
        $allowedHeights = config('image-proxy.allowed_heights');
        $allowedFormats = config('image-proxy.allowed_formats', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

        if ($allowedWidths !== null && isset($options['width'])) {
            abort_unless(in_array((int) $options['width'], $allowedWidths), 400, 'Invalid width');
        }

        if ($allowedHeights !== null && isset($options['height'])) {
            abort_unless(in_array((int) $options['height'], $allowedHeights), 400, 'Invalid height');
        }

        if (isset($options['format'])) {
            abort_unless(in_array(strtolower($options['format']), $allowedFormats), 400, 'Invalid format');
        }

        if (isset($options['quality'])) {
            $quality = (int) $options['quality'];
            abort_unless($quality >= 1 && $quality <= 100, 400, 'Quality must be between 1 and 100');
        }
    }

    protected function buildCacheControl(): string
    {
        $config = config('image-proxy.cache', []);
        $parts = [];

        $parts[] = 'public';

        if ($maxAge = Arr::get($config, 'max_age', 2592000)) {
            $parts[] = 'max-age=' . $maxAge;
        }

        if ($sMaxAge = Arr::get($config, 's_maxage', 2592000)) {
            $parts[] = 's-maxage=' . $sMaxAge;
        }

        if (Arr::get($config, 'immutable', true)) {
            $parts[] = 'immutable';
        }

        return implode(', ', $parts);
    }
}
