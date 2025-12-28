<?php

use TryHard\ImageProxy\PathValidator;

it('allows any path by default', function () {
    $validator = new PathValidator;

    expect($validator('public', 'anything/here.jpg'))->toBeTrue();
});

it('always blocks directory traversal', function () {
    $validator = PathValidator::secure();

    expect($validator('public', '../etc/passwd'))->toBeFalse();
    expect($validator('public', 'images/../../../etc/passwd'))->toBeFalse();
    expect($validator('public', 'images/photo.jpg'))->toBeTrue();
});

it('blocks traversal even with directories configured', function () {
    $validator = PathValidator::directories('images');

    expect($validator('public', 'images/../etc/passwd'))->toBeFalse();
});

it('restricts to specific directories', function () {
    $validator = PathValidator::directories('images', 'uploads');

    expect($validator('public', 'images/photo.jpg'))->toBeTrue();
    expect($validator('public', 'images/sub/photo.jpg'))->toBeTrue();
    expect($validator('public', 'uploads/file.png'))->toBeTrue();
    expect($validator('public', 'other/file.jpg'))->toBeFalse();
    expect($validator('public', 'photo.jpg'))->toBeFalse();
});

it('matches glob patterns with single wildcard', function () {
    $validator = PathValidator::matches('images/*.jpg');

    expect($validator('public', 'images/photo.jpg'))->toBeTrue();
    expect($validator('public', 'images/test.jpg'))->toBeTrue();
    expect($validator('public', 'images/sub/photo.jpg'))->toBeFalse();
    expect($validator('public', 'images/photo.png'))->toBeFalse();
});

it('matches glob patterns with double wildcard', function () {
    $validator = PathValidator::matches('images/**/*.jpg');

    expect($validator('public', 'images/photo.jpg'))->toBeTrue();
    expect($validator('public', 'images/sub/photo.jpg'))->toBeTrue();
    expect($validator('public', 'images/a/b/c/photo.jpg'))->toBeTrue();
    expect($validator('public', 'other/photo.jpg'))->toBeFalse();
});

it('matches glob patterns with question mark', function () {
    $validator = PathValidator::matches('img?.jpg');

    expect($validator('public', 'img1.jpg'))->toBeTrue();
    expect($validator('public', 'imgA.jpg'))->toBeTrue();
    expect($validator('public', 'img.jpg'))->toBeFalse();
    expect($validator('public', 'img12.jpg'))->toBeFalse();
});

it('accepts multiple patterns', function () {
    $validator = PathValidator::matches('images/*.jpg', 'photos/*.png');

    expect($validator('public', 'images/test.jpg'))->toBeTrue();
    expect($validator('public', 'photos/test.png'))->toBeTrue();
    expect($validator('public', 'images/test.png'))->toBeFalse();
    expect($validator('public', 'other/test.jpg'))->toBeFalse();
});
