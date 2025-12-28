<?php

namespace AaronFrancis\ImgProxy;

use Illuminate\Contracts\Support\Htmlable;
use Stringable;

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

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function w(int $width): static
    {
        return $this->width($width);
    }

    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function h(int $height): static
    {
        return $this->height($height);
    }

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

    public function quality(int $quality): static
    {
        $this->quality = $quality;

        return $this;
    }

    public function q(int $quality): static
    {
        return $this->quality($quality);
    }

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

    public function url(): string
    {
        return (string) $this;
    }

    public function toHtml(): string
    {
        return $this->url();
    }

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
