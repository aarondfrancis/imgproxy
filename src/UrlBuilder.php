<?php

namespace AaronFrancis\ImgProxy;

use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * Fluent builder for constructing image proxy URLs.
 *
 * Provides a chainable API for setting image transformation options
 * and generates properly formatted URLs for the image proxy endpoint.
 */
class UrlBuilder implements Stringable, Htmlable
{
    protected ?int $width = null;
    protected ?int $height = null;
    protected ?string $format = null;
    protected ?int $quality = null;
    protected ?string $fit = null;
    protected ?string $version = null;

    public function __construct(
        protected string $source,
        protected string $path
    ) {}

    /**
     * Set the target width in pixels.
     *
     * @param  int  $width  Width in pixels
     * @return static
     */
    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function w(int $width): static
    {
        return $this->width($width);
    }

    /**
     * Set the target height in pixels.
     *
     * @param  int  $height  Height in pixels
     * @return static
     */
    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function h(int $height): static
    {
        return $this->height($height);
    }

    /**
     * Set the output format.
     *
     * @param  string  $format  Output format (jpg, png, gif, webp)
     * @return static
     */
    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function f(string $format): static
    {
        return $this->format($format);
    }

    public function webp(): static
    {
        return $this->format('webp');
    }

    public function png(): static
    {
        return $this->format('png');
    }

    public function jpg(): static
    {
        return $this->format('jpg');
    }

    public function gif(): static
    {
        return $this->format('gif');
    }

    /**
     * Set the output quality for lossy formats.
     *
     * @param  int  $quality  Quality from 1-100
     * @return static
     */
    public function quality(int $quality): static
    {
        $this->quality = $quality;

        return $this;
    }

    public function q(int $quality): static
    {
        return $this->quality($quality);
    }

    /**
     * Set the resize fit mode.
     *
     * @param  string  $fit  Fit mode (scale, scaledown, cover, contain, crop)
     * @return static
     */
    public function fit(string $fit): static
    {
        $this->fit = $fit;

        return $this;
    }

    public function cover(): static
    {
        return $this->fit('cover');
    }

    public function contain(): static
    {
        return $this->fit('contain');
    }

    public function scale(): static
    {
        return $this->fit('scale');
    }

    public function scaleDown(): static
    {
        return $this->fit('scaledown');
    }

    public function crop(): static
    {
        return $this->fit('crop');
    }

    public function v(string|int $version): static
    {
        $this->version = (string) $version;

        return $this;
    }

    public function version(string|int $version): static
    {
        return $this->v($version);
    }

    /**
     * Get the generated URL string.
     *
     * @return string  The complete image proxy URL
     */
    public function url(): string
    {
        return (string) $this;
    }

    public function toHtml(): string
    {
        return $this->url();
    }

    /**
     * Convert the builder to a URL string.
     *
     * @return string  The complete image proxy URL
     */
    public function __toString(): string
    {
        $options = $this->buildOptions();
        $prefix = config('imgproxy.route.prefix');

        $parts = array_filter([
            $prefix,
            $options,
            $this->source,
            ltrim($this->path, '/'),
        ]);

        return '/' . implode('/', $parts);
    }

    protected function buildOptions(): string
    {
        $options = [];

        if ($this->width !== null) {
            $options[] = 'w=' . $this->width;
        }

        if ($this->height !== null) {
            $options[] = 'h=' . $this->height;
        }

        if ($this->fit !== null) {
            $options[] = 'fit=' . $this->fit;
        }

        if ($this->quality !== null) {
            $options[] = 'q=' . $this->quality;
        }

        if ($this->format !== null) {
            $options[] = 'f=' . $this->format;
        }

        if ($this->version !== null) {
            $options[] = 'v=' . $this->version;
        }

        return implode(',', $options);
    }
}
