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
        'prefix' => null,
        'middleware' => [],
        'name' => 'imgproxy.show',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Sources
    |--------------------------------------------------------------------------
    |
    | Define the filesystem disks that the proxy can serve images from. Each
    | source maps a URL prefix to a configuration array with:
    |
    |   - disk: The Laravel filesystem disk name
    |   - root: Optional subdirectory within the disk (validated automatically)
    |   - validator: Optional PathValidator or custom validator class
    |
    | For example, /w=800/images/photo.jpg serves photo.jpg from the configured
    | disk's root directory.
    |
    */

    'sources' => [
        'images' => [
            'disk' => 'public',
            // 'root' => null,      // Optional: subdirectory within disk
            // 'validator' => null, // Optional: PathValidator instance or class
        ],

        // Example with all options:
        // 'uploads' => [
        //     'disk' => 'public',
        //     'root' => 'uploads',
        //     'validator' => \AaronFrancis\ImgProxy\PathValidator::extensions(['jpg', 'png', 'webp']),
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limit how many times a single IP can request the same image path per
    | minute. This prevents abuse from requesting many different resize
    | variations of the same image. Only applies in production.
    |
    | Most images should be cached by your CDN, so legitimate users will
    | rarely hit your origin server at all. This is a safety net.
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
        'max_age' => 2592000,                  // 30 days
        's_maxage' => 2592000,                 // 30 days (for CDN/proxy caches)
        'stale_while_revalidate' => 86400,     // 1 day
        'stale_if_error' => 86400,             // 1 day
        'immutable' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Dimensions
    |--------------------------------------------------------------------------
    |
    | Hard caps on the maximum width and height that can be requested. This
    | prevents bad actors from requesting extremely large images to consume
    | server resources.
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
