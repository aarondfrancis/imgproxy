<?php

namespace TryHard\ImageProxy\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
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

        [$disk, $resolvedPath] = $this->resolveDisk($path);

        $this->validatePath($disk, $resolvedPath);

        $imageData = $this->loadImage($disk, $resolvedPath);
        $extension = pathinfo($resolvedPath, PATHINFO_EXTENSION);

        $image = Image::read($imageData);
        $parsedOptions = $this->parseOptions($options);

        $this->validateOptions($parsedOptions);

        if (Arr::hasAny($parsedOptions, ['width', 'height'])) {
            $width = $parsedOptions['width'] ?? null;
            $height = $parsedOptions['height'] ?? null;
            $fit = $parsedOptions['fit'] ?? 'scaledown';

            $image = match ($fit) {
                'scale' => $image->scale(width: $width, height: $height),
                'cover' => $image->cover((int) $width, (int) $height),
                'contain' => $image->contain((int) $width, (int) $height),
                'crop' => $image->crop((int) $width, (int) $height),
                default => $image->scaleDown(width: $width, height: $height),
            };
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

    protected function resolveDisk(string $path): array
    {
        $sources = config('image-proxy.sources', ['' => 'public']);

        foreach ($sources as $prefix => $disk) {
            if ($prefix === '') {
                continue;
            }

            if (Str::startsWith($path, $prefix . '/')) {
                $resolvedPath = Str::after($path, $prefix . '/');

                return [$disk, $resolvedPath];
            }
        }

        return [$sources[''] ?? 'public', $path];
    }

    protected function validatePath(string $disk, string $path): void
    {
        // Always prevent directory traversal
        abort_if(str_contains($path, '..'), 403);

        $validator = config('image-proxy.path_validator');

        if ($validator) {
            $validator = is_string($validator) ? app($validator) : $validator;
            abort_unless($validator($disk, $path), 403);
        }
    }

    protected function loadImage(string $disk, string $path): string
    {
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->get($path);
    }

    protected function parseOptions(string $options): array
    {
        $aliases = [
            'w' => 'width',
            'h' => 'height',
            'q' => 'quality',
            'f' => 'format',
        ];

        return collect(explode(',', $options))
            ->mapWithKeys(function ($opt) {
                $parts = explode('=', $opt, 2);

                return [$parts[0] => $parts[1] ?? null];
            })
            ->mapWithKeys(function ($value, $key) use ($aliases) {
                return [Arr::get($aliases, $key, $key) => $value];
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

        if (isset($options['fit'])) {
            $allowedFits = ['scale', 'scaledown', 'cover', 'contain', 'crop'];
            abort_unless(in_array(strtolower($options['fit']), $allowedFits), 400, 'Invalid fit mode');
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
