<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the route that serves proxied images. You can
    | change the URL prefix, apply middleware, or disable the route entirely
    | if you prefer to register your own. Set prefix to null to serve images
    | from the root URL (e.g., /{options}/{path}).
    |
    | Avoid middleware that starts sessions or sets cookies—these prevent
    | CDN caching and add unnecessary overhead for image requests.
    |
    */

    'route' => [
        'enabled' => true,
        'prefix' => 'img',
        'middleware' => [],
        'name' => 'image-proxy.show',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Sources
    |--------------------------------------------------------------------------
    |
    | Define the filesystem disks that the proxy can serve images from. Each
    | entry maps a URL prefix to a Laravel filesystem disk. Every source must
    | have an explicit prefix - empty prefixes are not allowed.
    |
    | For example, /img/w=800/p/photos/1.jpg serves photos/1.jpg from the
    | 'public' disk, and /img/w=800/r2/photos/1.jpg from the 'r2' disk.
    |
    */

    'sources' => [
        'p' => 'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Validation
    |--------------------------------------------------------------------------
    |
    | You may provide an optional validator to restrict which paths can be
    | served. Use the built-in PathValidator helpers or your own invokable
    | class. Directory traversal attacks (../) are always blocked.
    |
    | Examples:
    |   PathValidator::directories('images', 'uploads')
    |   PathValidator::matches('images/**\/*.jpg', 'photos/*.png')
    |
    */

    'path_validator' => null,

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limit how many times a single IP can request the same image path per
    | minute. This prevents abuse from requesting many different resize
    | variations of the same image. Only applies in production.
    |
    */

    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Quality
    |--------------------------------------------------------------------------
    |
    | When encoding JPEG or WebP images, this quality setting will be used
    | if no quality option is specified in the URL. Quality can be set per
    | request using the q= or quality= option (1-100).
    |
    */

    'default_quality' => 85,

    /*
    |--------------------------------------------------------------------------
    | Cache Headers
    |--------------------------------------------------------------------------
    |
    | Configure the Cache-Control headers sent with image responses. These
    | headers control how long browsers and CDNs cache the processed images.
    | The default values cache images for 30 days with immutable flag.
    |
    */

    'cache' => [
        'max_age' => 2592000,
        's_maxage' => 2592000,
        'immutable' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Dimensions
    |--------------------------------------------------------------------------
    |
    | Hard caps on the maximum width and height that can be requested. This
    | prevents bad actors from requesting extremely large images to consume
    | server resources. These limits apply regardless of allowed_widths or
    | allowed_heights settings.
    |
    */

    'max_width' => 2000,
    'max_height' => 2000,

    /*
    |--------------------------------------------------------------------------
    | Allowed Formats
    |--------------------------------------------------------------------------
    |
    | Define which output formats can be requested via the f= or format=
    | option. Requests for formats not in this list will be rejected with
    | a 400 response.
    |
    */

    'allowed_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],

];
