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
    */

    'route' => [
        'enabled' => true,
        'prefix' => 'img',
        'middleware' => ['web'],
        'name' => 'image-proxy.show',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Sources
    |--------------------------------------------------------------------------
    |
    | Define the filesystem disks that the proxy can serve images from. Each
    | entry maps a URL prefix to a Laravel filesystem disk. The empty string
    | key defines the default disk used when no prefix matches.
    |
    | For example, with 'r2' => 'r2', a request to /img/w=800/r2/photos/1.jpg
    | will serve photos/1.jpg from the 'r2' disk.
    |
    */

    'sources' => [
        '' => 'public',
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
    | To prevent abuse, you may enable rate limiting on image requests. When
    | enabled, each unique combination of IP address and image path is limited
    | to the specified number of requests. Rate limiting only applies in the
    | production environment.
    |
    */

    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 10,
        'key_prefix' => 'image-proxy',
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
    | Allowed Dimensions
    |--------------------------------------------------------------------------
    |
    | To prevent cache-busting attacks with arbitrary dimensions, you may
    | restrict the allowed width and height values to a whitelist. Set to
    | null to allow any dimensions, or provide an array of allowed values.
    |
    | Example: [100, 200, 400, 800, 1200, 1600]
    |
    */

    'allowed_widths' => null,
    'allowed_heights' => null,

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
