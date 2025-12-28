<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Create a 200x100 test image (2:1 aspect ratio)
    $image = imagecreatetruecolor(200, 100);
    $red = imagecolorallocate($image, 255, 0, 0);
    imagefill($image, 0, 0, $red);

    ob_start();
    imagejpeg($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put('test-image.jpg', $imageData);
});

afterEach(function () {
    Storage::disk('public')->delete('test-image.jpg');
});

it('resizes an image with width option', function () {
    $response = $this->get('/width=100/images/test-image.jpg');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/jpeg');
    $response->assertHeader('Cache-Control');

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
    expect(imagesy($image))->toBe(50); // maintains 2:1 aspect ratio
});

it('converts an image to webp format', function () {
    $response = $this->get('/format=webp/images/test-image.jpg');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/webp');
});

it('returns 404 for non-existent image', function () {
    $response = $this->get('/width=100/images/non-existent.jpg');

    $response->assertStatus(404);
});

it('returns 404 for unknown source', function () {
    $response = $this->get('/width=100/unknown/test-image.jpg');

    $response->assertStatus(404);
});

it('prevents directory traversal attacks', function () {
    $response = $this->get('/width=100/images/../../../etc/passwd.jpg');

    $response->assertStatus(403);
});

it('validates quality option range', function () {
    $response = $this->get('/quality=150/images/test-image.jpg');

    $response->assertStatus(400);
});

it('accepts valid quality option', function () {
    $response = $this->get('/q=80/images/test-image.jpg');

    $response->assertStatus(200);

    // Verify it's a valid image and quality affects file size
    $lowQuality = $this->get('/q=10/images/test-image.jpg');
    $highQuality = $this->get('/q=100/images/test-image.jpg');

    expect(strlen($lowQuality->getContent()))->toBeLessThan(strlen($highQuality->getContent()));
});

it('supports multiple options', function () {
    $response = $this->get('/w=50,h=25,fit=cover,q=80/images/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(50);
    expect(imagesy($image))->toBe(25);
});

it('supports option aliases', function () {
    $response = $this->get('/w=100,h=50,f=webp/images/test-image.jpg');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/webp');

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
    expect(imagesy($image))->toBe(50);
});

it('supports fit=cover mode', function () {
    // cover crops to fill exact dimensions
    $response = $this->get('/w=50,h=50,fit=cover/images/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(50);
    expect(imagesy($image))->toBe(50);
});

it('supports fit=contain mode', function () {
    // contain fits inside dimensions with padding
    $response = $this->get('/w=100,h=100,fit=contain/images/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
    expect(imagesy($image))->toBe(100); // padded to fit
});

it('supports fit=scale mode', function () {
    // scale maintains aspect ratio
    $response = $this->get('/w=100,h=100,fit=scale/images/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
    expect(imagesy($image))->toBe(50); // maintains 2:1 ratio
});

it('supports fit=crop mode', function () {
    // crop extracts exact dimensions from center
    $response = $this->get('/w=50,h=50,fit=crop/images/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(50);
    expect(imagesy($image))->toBe(50);
});

it('supports fit=scaledown mode', function () {
    // scaledown only shrinks, never enlarges
    $response = $this->get('/w=100,fit=scaledown/images/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
    expect(imagesy($image))->toBe(50);
});

it('rejects invalid fit mode', function () {
    $response = $this->get('/w=50,fit=invalid/images/test-image.jpg');

    $response->assertStatus(400);
});

it('rejects dimensions exceeding max', function () {
    $response = $this->get('/w=5000/images/test-image.jpg');

    $response->assertStatus(400);
});

it('allows v option as cache buster', function () {
    $response = $this->get('/w=100,v=2/images/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
});

it('uses root path from source config', function () {
    // Configure a source with a root path
    config(['imgproxy.sources.media' => [
        'disk' => 'public',
        'root' => 'uploads',
    ]]);

    // Create image in the root directory
    $image = imagecreatetruecolor(100, 100);
    ob_start();
    imagejpeg($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put('uploads/photo.jpg', $imageData);

    $response = $this->get('/w=50/media/photo.jpg');

    $response->assertStatus(200);

    Storage::disk('public')->delete('uploads/photo.jpg');
});

it('passes full path including root to validator', function () {
    $receivedPath = null;

    // Create a validator that captures the path it receives
    $validator = new class implements \AaronFrancis\ImgProxy\Contracts\PathValidatorContract {
        public static ?string $receivedPath = null;

        public function validate(string $path): bool
        {
            self::$receivedPath = $path;
            return true;
        }
    };

    config(['imgproxy.sources.media' => [
        'disk' => 'public',
        'root' => 'uploads/images',
        'validator' => $validator,
    ]]);

    // Create image in the root directory
    $image = imagecreatetruecolor(100, 100);
    ob_start();
    imagejpeg($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put('uploads/images/photo.jpg', $imageData);

    $response = $this->get('/w=50/media/photo.jpg');

    $response->assertStatus(200);

    // Validator should receive the FULL path including root
    expect($validator::$receivedPath)->toBe('uploads/images/photo.jpg');

    Storage::disk('public')->delete('uploads/images/photo.jpg');
});

it('validates root directory with PathValidator', function () {
    config(['imgproxy.sources.media' => [
        'disk' => 'public',
        'root' => 'uploads',
        'validator' => \AaronFrancis\ImgProxy\PathValidator::directories(['uploads']),
    ]]);

    // Create image in the root directory
    $image = imagecreatetruecolor(100, 100);
    ob_start();
    imagejpeg($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put('uploads/photo.jpg', $imageData);

    $response = $this->get('/w=50/media/photo.jpg');

    $response->assertStatus(200);

    Storage::disk('public')->delete('uploads/photo.jpg');
});

it('supports nested paths with slashes', function () {
    // Create image in nested directory
    $image = imagecreatetruecolor(100, 100);
    ob_start();
    imagejpeg($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put('photos/2024/january/photo.jpg', $imageData);

    $response = $this->get('/w=50/images/photos/2024/january/photo.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(50);

    Storage::disk('public')->delete('photos/2024/january/photo.jpg');
});

it('rejects paths outside allowed directories even with root', function () {
    config(['imgproxy.sources.media' => [
        'disk' => 'public',
        'root' => 'uploads',
        // Only allow uploads/safe, not uploads root
        'validator' => \AaronFrancis\ImgProxy\PathValidator::directories(['uploads/safe']),
    ]]);

    // Create image in root (not in safe subdirectory)
    $image = imagecreatetruecolor(100, 100);
    ob_start();
    imagejpeg($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put('uploads/photo.jpg', $imageData);

    $response = $this->get('/w=50/media/photo.jpg');

    // Should be rejected because uploads/photo.jpg is not in uploads/safe/
    $response->assertStatus(403);

    Storage::disk('public')->delete('uploads/photo.jpg');
});
