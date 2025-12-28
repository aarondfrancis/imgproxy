<?php

use AaronFrancis\ImgProxy\Contracts\PathValidatorContract;
use AaronFrancis\ImgProxy\ImgProxyService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// resolve() tests

it('resolves a valid source and path', function () {
    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
        ],
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('images', 'photo.jpg');

    expect($result)->toBe([
        'disk' => 'public',
        'path' => 'photo.jpg',
        'config' => [
            'disk' => 'public',
            'root' => null,
            'validator' => null,
        ],
    ]);
});

it('returns 404 for unknown source', function () {
    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
        ],
    ]]);

    $service = new ImgProxyService;
    $service->resolve('unknown', 'photo.jpg');
})->throws(HttpException::class, 'Unknown source');

it('blocks directory traversal in path', function () {
    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
        ],
    ]]);

    $service = new ImgProxyService;
    $service->resolve('images', '../etc/passwd');
})->throws(HttpException::class, 'Directory traversal not allowed');

it('blocks directory traversal with nested attempts', function () {
    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
        ],
    ]]);

    $service = new ImgProxyService;
    $service->resolve('images', 'photos/../../../etc/passwd');
})->throws(HttpException::class, 'Directory traversal not allowed');

it('prepends root path when configured', function () {
    config(['imgproxy.sources' => [
        'media' => [
            'disk' => 'local',
            'root' => 'uploads',
        ],
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('media', 'photo.jpg');

    expect($result['path'])->toBe('uploads/photo.jpg');
});

it('prepends root path with trailing slash', function () {
    config(['imgproxy.sources' => [
        'media' => [
            'disk' => 'local',
            'root' => 'uploads/',
        ],
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('media', 'photo.jpg');

    expect($result['path'])->toBe('uploads/photo.jpg');
});

it('prepends root path to nested paths', function () {
    config(['imgproxy.sources' => [
        'media' => [
            'disk' => 'local',
            'root' => 'uploads/images',
        ],
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('media', '2024/photo.jpg');

    expect($result['path'])->toBe('uploads/images/2024/photo.jpg');
});

// normalizeConfig() tests

it('normalizes string config to array', function () {
    config(['imgproxy.sources' => [
        'images' => 'local',
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('images', 'photo.jpg');

    expect($result['config'])->toBe([
        'disk' => 'local',
        'root' => null,
        'validator' => null,
    ]);
});

it('normalizes array config with defaults', function () {
    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 's3',
        ],
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('images', 'photo.jpg');

    expect($result['config'])->toBe([
        'disk' => 's3',
        'root' => null,
        'validator' => null,
    ]);
});

it('normalizes array config with all options', function () {
    $validator = fn ($path) => true;

    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 's3',
            'root' => 'media',
            'validator' => $validator,
        ],
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('images', 'photo.jpg');

    expect($result['config']['disk'])->toBe('s3');
    expect($result['config']['root'])->toBe('media');
    expect($result['config']['validator'])->toBe($validator);
});

it('uses public as default disk when not specified', function () {
    config(['imgproxy.sources' => [
        'images' => [
            'root' => 'uploads',
        ],
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('images', 'photo.jpg');

    expect($result['disk'])->toBe('public');
});

// validatePath() tests with PathValidatorContract

it('validates path with PathValidatorContract implementation', function () {
    $validator = new class implements PathValidatorContract
    {
        public function validate(string $path): bool
        {
            return str_starts_with($path, 'allowed/');
        }
    };

    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
            'validator' => $validator,
        ],
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('images', 'allowed/photo.jpg');

    expect($result['path'])->toBe('allowed/photo.jpg');
});

it('rejects path when PathValidatorContract returns false', function () {
    $validator = new class implements PathValidatorContract
    {
        public function validate(string $path): bool
        {
            return str_starts_with($path, 'allowed/');
        }
    };

    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
            'validator' => $validator,
        ],
    ]]);

    $service = new ImgProxyService;
    $service->resolve('images', 'forbidden/photo.jpg');
})->throws(HttpException::class, 'Path not allowed');

it('resolves string validator from container', function () {
    $validator = new class implements PathValidatorContract
    {
        public function validate(string $path): bool
        {
            return true;
        }
    };

    app()->instance('test.validator', $validator);

    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
            'validator' => 'test.validator',
        ],
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('images', 'photo.jpg');

    expect($result['path'])->toBe('photo.jpg');
});

it('resolves class name validator from container', function () {
    $validatorClass = new class implements PathValidatorContract
    {
        public static bool $validated = false;

        public function validate(string $path): bool
        {
            self::$validated = true;

            return true;
        }
    };

    app()->instance(get_class($validatorClass), $validatorClass);

    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
            'validator' => get_class($validatorClass),
        ],
    ]]);

    $service = new ImgProxyService;
    $service->resolve('images', 'photo.jpg');

    expect($validatorClass::$validated)->toBeTrue();
});

// validatePath() tests with callable validators

it('validates path with callable validator', function () {
    $validated = false;

    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
            'validator' => function (string $path) use (&$validated) {
                $validated = true;

                return str_ends_with($path, '.jpg');
            },
        ],
    ]]);

    $service = new ImgProxyService;
    $service->resolve('images', 'photo.jpg');

    expect($validated)->toBeTrue();
});

it('rejects path when callable validator returns false', function () {
    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
            'validator' => fn (string $path) => str_ends_with($path, '.png'),
        ],
    ]]);

    $service = new ImgProxyService;
    $service->resolve('images', 'photo.jpg');
})->throws(HttpException::class, 'Path not allowed');

it('passes full path including root to callable validator', function () {
    $receivedPath = null;

    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
            'root' => 'uploads/media',
            'validator' => function (string $path) use (&$receivedPath) {
                $receivedPath = $path;

                return true;
            },
        ],
    ]]);

    $service = new ImgProxyService;
    $service->resolve('images', 'photo.jpg');

    expect($receivedPath)->toBe('uploads/media/photo.jpg');
});

// validatePath() tests with null validator

it('allows any path when validator is null', function () {
    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
            'validator' => null,
        ],
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('images', 'any/path/photo.jpg');

    expect($result['path'])->toBe('any/path/photo.jpg');
});

it('allows any path when validator is not configured', function () {
    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
        ],
    ]]);

    $service = new ImgProxyService;
    $result = $service->resolve('images', 'any/nested/path/photo.jpg');

    expect($result['path'])->toBe('any/nested/path/photo.jpg');
});

// loadImage() tests

it('loads an existing image from disk', function () {
    $imageContent = 'fake-image-content';

    Storage::fake('public');
    Storage::disk('public')->put('photo.jpg', $imageContent);

    $service = new ImgProxyService;
    $result = $service->loadImage('public', 'photo.jpg');

    expect($result)->toBe($imageContent);
});

it('loads an image from nested path', function () {
    $imageContent = 'nested-image-content';

    Storage::fake('public');
    Storage::disk('public')->put('uploads/2024/photo.jpg', $imageContent);

    $service = new ImgProxyService;
    $result = $service->loadImage('public', 'uploads/2024/photo.jpg');

    expect($result)->toBe($imageContent);
});

it('returns 404 when image does not exist', function () {
    Storage::fake('public');

    $service = new ImgProxyService;
    $service->loadImage('public', 'non-existent.jpg');
})->throws(NotFoundHttpException::class, 'Image not found');

it('returns 404 for non-existent path in nested directory', function () {
    Storage::fake('public');
    Storage::disk('public')->put('uploads/other.jpg', 'content');

    $service = new ImgProxyService;
    $service->loadImage('public', 'uploads/non-existent.jpg');
})->throws(NotFoundHttpException::class, 'Image not found');

it('loads image from different disk', function () {
    $imageContent = 'local-disk-image';

    Storage::fake('local');
    Storage::disk('local')->put('images/photo.jpg', $imageContent);

    $service = new ImgProxyService;
    $result = $service->loadImage('local', 'images/photo.jpg');

    expect($result)->toBe($imageContent);
});

// Integration tests - resolve and loadImage together

it('resolves and loads image successfully', function () {
    Storage::fake('public');
    Storage::disk('public')->put('photos/image.jpg', 'image-data');

    config(['imgproxy.sources' => [
        'gallery' => [
            'disk' => 'public',
            'root' => 'photos',
        ],
    ]]);

    $service = new ImgProxyService;
    $resolved = $service->resolve('gallery', 'image.jpg');
    $content = $service->loadImage($resolved['disk'], $resolved['path']);

    expect($content)->toBe('image-data');
});

it('validates before loading with custom validator', function () {
    Storage::fake('public');
    Storage::disk('public')->put('private/secret.jpg', 'secret-data');

    config(['imgproxy.sources' => [
        'images' => [
            'disk' => 'public',
            'validator' => fn ($path) => ! str_starts_with($path, 'private/'),
        ],
    ]]);

    $service = new ImgProxyService;
    $service->resolve('images', 'private/secret.jpg');
})->throws(HttpException::class, 'Path not allowed');
