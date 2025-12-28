<?php

namespace AaronFrancis\ImgProxy\Http\Controllers;

use AaronFrancis\ImgProxy\ImgProxyService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class ImgProxyController extends Controller
{
    public function __construct(
        protected ImgProxyService $service
    ) {}

    public function show(Request $request, string $options, string $source, string $path): Response
    {
        if ($this->shouldRateLimit()) {
            $this->rateLimit($request, $source . '/' . $path);
        }

        $resolved = $this->service->resolve($source, $path);
        $imageData = $this->service->loadImage($resolved['disk'], $resolved['path']);

        $extension = pathinfo($resolved['path'], PATHINFO_EXTENSION);
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

        $quality = (int) Arr::get($parsedOptions, 'quality', config('imgproxy.default_quality', 85));
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
        return App::isProduction() && config('imgproxy.rate_limit.enabled', true);
    }

    protected function rateLimit(Request $request, string $path): void
    {
        $maxAttempts = config('imgproxy.rate_limit.max_attempts', 10);

        $allowed = RateLimiter::attempt(
            key: 'imgproxy::' . $request->ip() . ':' . $path,
            maxAttempts: $maxAttempts,
            callback: fn () => true
        );

        if (! $allowed) {
            throw new HttpResponseException(Redirect::to($path));
        }
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
        $maxWidth = config('imgproxy.max_width', 2000);
        $maxHeight = config('imgproxy.max_height', 2000);
        $allowedFormats = config('imgproxy.allowed_formats', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

        if (isset($options['width'])) {
            $width = (int) $options['width'];
            abort_if($width < 1 || $width > $maxWidth, 400, 'Invalid width');
        }

        if (isset($options['height'])) {
            $height = (int) $options['height'];
            abort_if($height < 1 || $height > $maxHeight, 400, 'Invalid height');
        }

        if (isset($options['format'])) {
            abort_unless(in_array(strtolower($options['format']), $allowedFormats), 400, 'Invalid format');
        }

        if (isset($options['quality'])) {
            $quality = (int) $options['quality'];
            abort_if($quality < 1 || $quality > 100, 400, 'Invalid quality');
        }

        if (isset($options['fit'])) {
            $allowedFits = ['scale', 'scaledown', 'cover', 'contain', 'crop'];
            abort_unless(in_array(strtolower($options['fit']), $allowedFits), 400, 'Invalid fit');
        }
    }

    protected function buildCacheControl(): string
    {
        $config = config('imgproxy.cache', []);
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
