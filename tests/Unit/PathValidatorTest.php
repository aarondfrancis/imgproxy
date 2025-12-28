<?php

use AaronFrancis\ImgProxy\PathValidator;

it('allows any path by default', function () {
    $validator = new PathValidator;

    expect($validator->validate('anything/here.jpg'))->toBeTrue();
});

it('always blocks directory traversal', function () {
    $validator = new PathValidator;

    expect($validator->validate('../etc/passwd'))->toBeFalse();
    expect($validator->validate('images/../../../etc/passwd'))->toBeFalse();
    expect($validator->validate('images/photo.jpg'))->toBeTrue();
});

it('blocks traversal even with directories configured', function () {
    $validator = PathValidator::directories(['images']);

    expect($validator->validate('images/../etc/passwd'))->toBeFalse();
});

it('restricts to specific directories', function () {
    $validator = PathValidator::directories(['images', 'uploads']);

    expect($validator->validate('images/photo.jpg'))->toBeTrue();
    expect($validator->validate('images/sub/photo.jpg'))->toBeTrue();
    expect($validator->validate('uploads/file.png'))->toBeTrue();
    expect($validator->validate('other/file.jpg'))->toBeFalse();
    expect($validator->validate('photo.jpg'))->toBeFalse();
});

it('matches glob patterns with single wildcard', function () {
    $validator = PathValidator::matches(['images/*.jpg']);

    expect($validator->validate('images/photo.jpg'))->toBeTrue();
    expect($validator->validate('images/test.jpg'))->toBeTrue();
    expect($validator->validate('images/sub/photo.jpg'))->toBeFalse();
    expect($validator->validate('images/photo.png'))->toBeFalse();
});

it('matches glob patterns with double wildcard', function () {
    $validator = PathValidator::matches(['images/**/*.jpg']);

    expect($validator->validate('images/photo.jpg'))->toBeTrue();
    expect($validator->validate('images/sub/photo.jpg'))->toBeTrue();
    expect($validator->validate('images/a/b/c/photo.jpg'))->toBeTrue();
    expect($validator->validate('other/photo.jpg'))->toBeFalse();
});

it('matches glob patterns with question mark', function () {
    $validator = PathValidator::matches(['img?.jpg']);

    expect($validator->validate('img1.jpg'))->toBeTrue();
    expect($validator->validate('imgA.jpg'))->toBeTrue();
    expect($validator->validate('img.jpg'))->toBeFalse();
    expect($validator->validate('img12.jpg'))->toBeFalse();
});

it('accepts multiple patterns', function () {
    $validator = PathValidator::matches(['images/*.jpg', 'photos/*.png']);

    expect($validator->validate('images/test.jpg'))->toBeTrue();
    expect($validator->validate('photos/test.png'))->toBeTrue();
    expect($validator->validate('images/test.png'))->toBeFalse();
    expect($validator->validate('other/test.jpg'))->toBeFalse();
});

it('restricts by file extension', function () {
    $validator = PathValidator::extensions(['jpg', 'png']);

    expect($validator->validate('photo.jpg'))->toBeTrue();
    expect($validator->validate('photo.JPG'))->toBeTrue();
    expect($validator->validate('photo.png'))->toBeTrue();
    expect($validator->validate('photo.gif'))->toBeFalse();
    expect($validator->validate('photo.webp'))->toBeFalse();
});

it('chains directories and extensions', function () {
    $validator = PathValidator::directories(['images'])
        ->withExtensions(['jpg', 'png']);

    expect($validator->validate('images/photo.jpg'))->toBeTrue();
    expect($validator->validate('images/photo.png'))->toBeTrue();
    expect($validator->validate('images/photo.gif'))->toBeFalse();
    expect($validator->validate('uploads/photo.jpg'))->toBeFalse();
});

it('chains multiple restrictions', function () {
    $validator = PathValidator::directories(['images', 'uploads'])
        ->withExtensions(['jpg'])
        ->matching(['**/*.jpg']);

    expect($validator->validate('images/photo.jpg'))->toBeTrue();
    expect($validator->validate('images/sub/photo.jpg'))->toBeTrue();
    expect($validator->validate('images/photo.png'))->toBeFalse();
    expect($validator->validate('other/photo.jpg'))->toBeFalse();
});
