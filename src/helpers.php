<?php

use AaronFrancis\ImgProxy\UrlBuilder;

if (! function_exists('imgproxy')) {
    function imgproxy(string $source, string $path): UrlBuilder
    {
        return new UrlBuilder($source, $path);
    }
}
