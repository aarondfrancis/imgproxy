<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the route prefix and middleware for the image proxy endpoint.
    |
    */

    'route' => [
        'enabled' => true,
        'prefix' => 'img', // Set to null or '' to serve from root
        'middleware' => ['web'],
        'name' => 'image-proxy.show',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Enable rate limiting to prevent abuse. When enabled, each unique
    | IP + path combination is limited to the specified number of requests.
    |
    */

    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 10,
        'key_prefix' => 'image-proxy',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sources
    |--------------------------------------------------------------------------
    |
    | Define the image sources that the proxy can serve from. Each source
    | maps a URL prefix to a filesystem disk name.
    |
    | Examples:
    | - '' => 'public'     (default, serves from 'public' disk)
    | - 'r2' => 'r2'       (/img/w=800/r2/path serves from 'r2' disk)
    | - 'media' => 's3'    (/img/w=800/media/path serves from 's3' disk)
    |
    */

    'sources' => [
        '' => 'public',
        // 'r2' => 'r2',
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Validation
    |--------------------------------------------------------------------------
    |
    | Optional callback to validate paths before serving. Return false to
    | reject the request with a 403 response.
    |
    | Example: fn(string $disk, string $path) => !str_contains($path, '..')
    |
    */

    'path_validator' => null,

    /*
    |--------------------------------------------------------------------------
    | Default Quality
    |--------------------------------------------------------------------------
    |
    | The default quality for JPEG and WebP encoding when not specified
    | in the URL options.
    |
    */

    'default_quality' => 85,

    /*
    |--------------------------------------------------------------------------
    | Cache Headers
    |--------------------------------------------------------------------------
    |
    | Configure the cache headers sent with image responses.
    |
    */

    'cache' => [
        'max_age' => 2592000, // 30 days
        's_maxage' => 2592000,
        'immutable' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Dimensions
    |--------------------------------------------------------------------------
    |
    | Optionally restrict the allowed width/height values. Set to null to
    | allow any dimensions. Use an array of allowed values for whitelist.
    |
    */

    'allowed_widths' => null,  // e.g., [100, 200, 400, 800, 1200]
    'allowed_heights' => null, // e.g., [100, 200, 400, 800]

    /*
    |--------------------------------------------------------------------------
    | Allowed Formats
    |--------------------------------------------------------------------------
    |
    | The output formats that can be requested via the format option.
    |
    */

    'allowed_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
];
