<?php

it('builds a basic url', function () {
    $url = imgproxy('images', 'photo.jpg');

    expect((string) $url)->toBe('/images/photo.jpg');
});

it('adds width option', function () {
    $url = imgproxy('images', 'photo.jpg')->width(400);

    expect((string) $url)->toBe('/w=400/images/photo.jpg');
});

it('adds height option', function () {
    $url = imgproxy('images', 'photo.jpg')->height(300);

    expect((string) $url)->toBe('/h=300/images/photo.jpg');
});

it('adds multiple options', function () {
    $url = imgproxy('images', 'photo.jpg')
        ->width(400)
        ->height(300)
        ->quality(80)
        ->format('webp');

    expect((string) $url)->toBe('/w=400,h=300,q=80,f=webp/images/photo.jpg');
});

it('supports aliases', function () {
    $url = imgproxy('images', 'photo.jpg')
        ->w(400)
        ->h(300)
        ->q(80)
        ->f('webp');

    expect((string) $url)->toBe('/w=400,h=300,q=80,f=webp/images/photo.jpg');
});

it('supports format shortcuts', function () {
    expect((string) imgproxy('images', 'photo.jpg')->webp())->toBe('/f=webp/images/photo.jpg');
    expect((string) imgproxy('images', 'photo.jpg')->png())->toBe('/f=png/images/photo.jpg');
    expect((string) imgproxy('images', 'photo.jpg')->jpg())->toBe('/f=jpg/images/photo.jpg');
    expect((string) imgproxy('images', 'photo.jpg')->gif())->toBe('/f=gif/images/photo.jpg');
});

it('supports fit modes', function () {
    expect((string) imgproxy('images', 'photo.jpg')->cover())->toBe('/fit=cover/images/photo.jpg');
    expect((string) imgproxy('images', 'photo.jpg')->contain())->toBe('/fit=contain/images/photo.jpg');
    expect((string) imgproxy('images', 'photo.jpg')->scale())->toBe('/fit=scale/images/photo.jpg');
    expect((string) imgproxy('images', 'photo.jpg')->scaleDown())->toBe('/fit=scaledown/images/photo.jpg');
    expect((string) imgproxy('images', 'photo.jpg')->crop())->toBe('/fit=crop/images/photo.jpg');
});

it('supports fit method', function () {
    $url = imgproxy('images', 'photo.jpg')
        ->width(400)
        ->height(300)
        ->fit('cover');

    expect((string) $url)->toBe('/w=400,h=300,fit=cover/images/photo.jpg');
});

it('supports version for cache busting', function () {
    $url = imgproxy('images', 'photo.jpg')
        ->width(400)
        ->v(2);

    expect((string) $url)->toBe('/w=400,v=2/images/photo.jpg');
});

it('supports version alias', function () {
    $url = imgproxy('images', 'photo.jpg')
        ->width(400)
        ->version('abc123');

    expect((string) $url)->toBe('/w=400,v=abc123/images/photo.jpg');
});

it('handles nested paths', function () {
    $url = imgproxy('images', 'photos/2024/january/photo.jpg')->width(400);

    expect((string) $url)->toBe('/w=400/images/photos/2024/january/photo.jpg');
});

it('strips leading slash from path', function () {
    $url = imgproxy('images', '/photo.jpg')->width(400);

    expect((string) $url)->toBe('/w=400/images/photo.jpg');
});

it('respects route prefix config', function () {
    config(['imgproxy.route.prefix' => 'img']);

    $url = imgproxy('images', 'photo.jpg')->width(400);

    expect((string) $url)->toBe('/img/w=400/images/photo.jpg');

    config(['imgproxy.route.prefix' => null]);
});

it('provides url method', function () {
    $url = imgproxy('images', 'photo.jpg')->width(400)->url();

    expect($url)->toBe('/w=400/images/photo.jpg');
});

it('implements Stringable', function () {
    $builder = imgproxy('images', 'photo.jpg')->width(400);

    expect($builder)->toBeInstanceOf(Stringable::class);
});

it('implements Htmlable for blade', function () {
    $builder = imgproxy('images', 'photo.jpg')->width(400);

    expect($builder)->toBeInstanceOf(\Illuminate\Contracts\Support\Htmlable::class);
    expect($builder->toHtml())->toBe('/w=400/images/photo.jpg');
});

it('can be echoed directly in blade', function () {
    $url = imgproxy('images', 'photo.jpg')->width(400);

    // Simulating what Blade does with Htmlable objects
    expect(e($url))->toBe('/w=400/images/photo.jpg');
});
