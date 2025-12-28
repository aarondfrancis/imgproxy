<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Create a test image in the public directory
    $testImagePath = public_path('test-image.jpg');

    if (!File::exists($testImagePath)) {
        // Create a simple 1x1 red JPEG
        $image = imagecreatetruecolor(100, 100);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $red);
        imagejpeg($image, $testImagePath);
        imagedestroy($image);
    }
});

afterEach(function () {
    $testImagePath = public_path('test-image.jpg');

    if (File::exists($testImagePath)) {
        File::delete($testImagePath);
    }
});

it('resizes an image with width option', function () {
    $response = $this->get('/img/width=50/test-image.jpg');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/jpeg');
    $response->assertHeader('Cache-Control');
});

it('converts an image to webp format', function () {
    $response = $this->get('/img/format=webp/test-image.jpg');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/webp');
});

it('returns 404 for non-existent image', function () {
    $response = $this->get('/img/width=100/non-existent.jpg');

    $response->assertStatus(404);
});

it('prevents directory traversal attacks', function () {
    $response = $this->get('/img/width=100/../../../etc/passwd.jpg');

    $response->assertStatus(403);
});

it('validates quality option range', function () {
    $response = $this->get('/img/quality=150/test-image.jpg');

    $response->assertStatus(400);
});

it('accepts valid quality option', function () {
    $response = $this->get('/img/quality=80/test-image.jpg');

    $response->assertStatus(200);
});

it('supports multiple options', function () {
    $response = $this->get('/img/width=50,height=50,quality=80/test-image.jpg');

    $response->assertStatus(200);
});
