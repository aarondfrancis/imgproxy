<?php

namespace AaronFrancis\ImgProxy;

use AaronFrancis\ImgProxy\Contracts\PathValidatorContract;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImgProxyService
{
    /**
     * Resolve a request path to disk, full path, and source config.
     *
     * @return array{disk: string, path: string, config: array}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function resolve(string $path): array
    {
        $sources = config('imgproxy.sources', []);

        foreach ($sources as $prefix => $config) {
            if ($prefix === '') {
                continue;
            }

            if (! Str::startsWith($path, $prefix . '/')) {
                continue;
            }

            $relativePath = Str::after($path, $prefix . '/');
            $config = $this->normalizeConfig($config);

            $this->validatePath($relativePath, $config);

            $fullPath = $config['root']
                ? rtrim($config['root'], '/') . '/' . $relativePath
                : $relativePath;

            return [
                'disk' => $config['disk'],
                'path' => $fullPath,
                'config' => $config,
            ];
        }

        abort(404, 'Unknown source');
    }

    /**
     * Normalize source config to array format.
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
     * Validate the path against security and custom validators.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function validatePath(string $path, array $config): void
    {
        // Always block directory traversal
        abort_if(str_contains($path, '..'), 403, 'Directory traversal not allowed');

        $validator = $config['validator'];

        if (! $validator) {
            return;
        }

        if (is_string($validator)) {
            $validator = app($validator);
        }

        if ($validator instanceof PathValidatorContract) {
            abort_unless($validator->validate($path), 403, 'Path not allowed');
        } elseif (is_callable($validator)) {
            abort_unless($validator($path), 403, 'Path not allowed');
        }
    }

    /**
     * Load an image from disk.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function loadImage(string $disk, string $path): string
    {
        abort_unless(Storage::disk($disk)->exists($path), 404, 'Image not found');

        return Storage::disk($disk)->get($path);
    }
}
