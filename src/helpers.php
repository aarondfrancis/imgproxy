<?php

use AaronFrancis\ImgProxy\UrlBuilder;

if (! function_exists('imgproxy')) {
    /**
     * Create a new image proxy URL builder.
     *
     * Usage:
     *   imgproxy('uploads', 'photos/image.jpg')->width(300)->webp()->url()
     *
     * @param  string  $source  The configured source identifier
     * @param  string  $path  Path to the image within the source
     * @return UrlBuilder Fluent builder for constructing the URL
     */
    function imgproxy(string $source, string $path): UrlBuilder
    {
        return new UrlBuilder($source, $path);
    }
}
