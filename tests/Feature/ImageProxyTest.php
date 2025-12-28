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
    $response = $this->get('/img/width=100/p/test-image.jpg');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/jpeg');
    $response->assertHeader('Cache-Control');

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
    expect(imagesy($image))->toBe(50); // maintains 2:1 aspect ratio
});

it('converts an image to webp format', function () {
    $response = $this->get('/img/format=webp/p/test-image.jpg');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/webp');
});

it('returns 404 for non-existent image', function () {
    $response = $this->get('/img/width=100/p/non-existent.jpg');

    $response->assertStatus(404);
});

it('returns 404 for unknown source', function () {
    $response = $this->get('/img/width=100/unknown/test-image.jpg');

    $response->assertStatus(404);
});

it('prevents directory traversal attacks', function () {
    $response = $this->get('/img/width=100/p/../../../etc/passwd.jpg');

    $response->assertStatus(403);
});

it('validates quality option range', function () {
    $response = $this->get('/img/quality=150/p/test-image.jpg');

    $response->assertStatus(400);
});

it('accepts valid quality option', function () {
    $response = $this->get('/img/q=80/p/test-image.jpg');

    $response->assertStatus(200);

    // Verify it's a valid image and quality affects file size
    $lowQuality = $this->get('/img/q=10/p/test-image.jpg');
    $highQuality = $this->get('/img/q=100/p/test-image.jpg');

    expect(strlen($lowQuality->getContent()))->toBeLessThan(strlen($highQuality->getContent()));
});

it('supports multiple options', function () {
    $response = $this->get('/img/w=50,h=25,fit=cover,q=80/p/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(50);
    expect(imagesy($image))->toBe(25);
});

it('supports option aliases', function () {
    $response = $this->get('/img/w=100,h=50,f=webp/p/test-image.jpg');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/webp');

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
    expect(imagesy($image))->toBe(50);
});

it('supports fit=cover mode', function () {
    // cover crops to fill exact dimensions
    $response = $this->get('/img/w=50,h=50,fit=cover/p/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(50);
    expect(imagesy($image))->toBe(50);
});

it('supports fit=contain mode', function () {
    // contain fits inside dimensions with padding
    $response = $this->get('/img/w=100,h=100,fit=contain/p/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
    expect(imagesy($image))->toBe(100); // padded to fit
});

it('supports fit=scale mode', function () {
    // scale maintains aspect ratio
    $response = $this->get('/img/w=100,h=100,fit=scale/p/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
    expect(imagesy($image))->toBe(50); // maintains 2:1 ratio
});

it('supports fit=crop mode', function () {
    // crop extracts exact dimensions from center
    $response = $this->get('/img/w=50,h=50,fit=crop/p/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(50);
    expect(imagesy($image))->toBe(50);
});

it('supports fit=scaledown mode', function () {
    // scaledown only shrinks, never enlarges
    $response = $this->get('/img/w=100,fit=scaledown/p/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
    expect(imagesy($image))->toBe(50);
});

it('rejects invalid fit mode', function () {
    $response = $this->get('/img/w=50,fit=invalid/p/test-image.jpg');

    $response->assertStatus(400);
});

it('rejects dimensions exceeding max', function () {
    $response = $this->get('/img/w=5000/p/test-image.jpg');

    $response->assertStatus(400);
});

it('allows v option as cache buster', function () {
    $response = $this->get('/img/w=100,v=2/p/test-image.jpg');

    $response->assertStatus(200);

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe(100);
});
