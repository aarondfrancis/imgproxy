<?php

namespace AaronFrancis\ImgProxy;

use AaronFrancis\ImgProxy\Contracts\PathValidatorContract;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImgProxyService
{
    /**
     * Resolve a source and path to disk, full path, and source config.
     *
     * @param  string  $source  The configured source identifier
     * @param  string  $path  The image path within the source
     * @return array{disk: string, path: string, config: array}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function resolve(string $source, string $path): array
    {
        $sources = config('imgproxy.sources', []);

        if (! isset($sources[$source])) {
            abort(404, 'Unknown source');
        }

        $config = $this->normalizeConfig($sources[$source]);

        // Always block directory traversal first
        abort_if(str_contains($path, '..'), 403, 'Directory traversal not allowed');

        // Prepend root if configured
        if ($config['root']) {
            $path = Str::start($path, rtrim($config['root'], '/').'/');
        }

        // Validate the full path (user validator sees the complete path including root)
        $this->validatePath($path, $config);

        return [
            'disk' => $config['disk'],
            'path' => $path,
            'config' => $config,
        ];
    }

    /**
     * Normalize source config to array format.
     *
     * @param  array|string  $config  Source config (string disk name or full config array)
     * @return array{disk: string, root: string|null, validator: mixed}
     */
    protected function normalizeConfig(array|string $config): array
    {
        if (is_string($config)) {
            return [
                'disk' => $config,
                'root' => null,
                'validator' => null,
            ];
        }

        return [
            'disk' => $config['disk'] ?? 'public',
            'root' => $config['root'] ?? null,
            'validator' => $config['validator'] ?? null,
        ];
    }

    /**
     * Validate the path against custom validators.
     *
     * @param  string  $fullPath  The full path including any configured root
     * @param  array  $config  The normalized source configuration
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function validatePath(string $fullPath, array $config): void
    {
        $validator = $config['validator'];

        if (! $validator) {
            return;
        }

        if (is_string($validator)) {
            $validator = app($validator);
        }

        if ($validator instanceof PathValidatorContract) {
            abort_unless($validator->validate($fullPath), 403, 'Path not allowed');
        } elseif (is_callable($validator)) {
            abort_unless($validator($fullPath), 403, 'Path not allowed');
        }
    }

    /**
     * Load an image from disk.
     *
     * @param  string  $disk  The filesystem disk name
     * @param  string  $path  The path to the image file
     * @return string The raw image data
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function loadImage(string $disk, string $path): string
    {
        abort_unless(Storage::disk($disk)->exists($path), 404, 'Image not found');

        return Storage::disk($disk)->get($path);
    }
}
