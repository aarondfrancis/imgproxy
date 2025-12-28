<?php

namespace AaronFrancis\ImgProxy;

use AaronFrancis\ImgProxy\Contracts\PathValidatorContract;

class PathValidator implements PathValidatorContract
{
    protected array $directories = [];

    protected array $patterns = [];

    protected array $extensions = [];

    public function validate(string $path): bool
    {
        if (str_contains($path, '..')) {
            return false;
        }

        if ($this->directories && ! $this->matchesDirectories($path)) {
            return false;
        }

        if ($this->patterns && ! $this->matchesPatterns($path)) {
            return false;
        }

        if ($this->extensions && ! $this->matchesExtensions($path)) {
            return false;
        }

        return true;
    }

    protected function matchesDirectories(string $path): bool
    {
        foreach ($this->directories as $dir) {
            $dir = rtrim($dir, '/') . '/';
            if (str_starts_with($path, $dir)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesPatterns(string $path): bool
    {
        foreach ($this->patterns as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesExtensions(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, array_map('strtolower', $this->extensions));
    }

    /**
     * Only allow paths within the specified directories.
     */
    public static function directories(array $directories): static
    {
        $validator = new static;
        $validator->directories = $directories;

        return $validator;
    }

    /**
     * Only allow paths matching the specified glob-like patterns.
     *
     * Supports:
     *   - * matches any characters except /
     *   - ** matches any characters including /
     *   - ? matches a single character
     */
    public static function matches(array $patterns): static
    {
        $validator = new static;
        $validator->patterns = $patterns;

        return $validator;
    }

    /**
     * Only allow paths with the specified file extensions.
     */
    public static function extensions(array $extensions): static
    {
        $validator = new static;
        $validator->extensions = $extensions;

        return $validator;
    }

    /**
     * Add directory restrictions (chainable).
     */
    public function inDirectories(array $directories): static
    {
        $this->directories = array_merge($this->directories, $directories);

        return $this;
    }

    /**
     * Add pattern restrictions (chainable).
     */
    public function matching(array $patterns): static
    {
        $this->patterns = array_merge($this->patterns, $patterns);

        return $this;
    }

    /**
     * Add extension restrictions (chainable).
     */
    public function withExtensions(array $extensions): static
    {
        $this->extensions = array_merge($this->extensions, $extensions);

        return $this;
    }

    protected function matchesPattern(string $path, string $pattern): bool
    {
        $regex = preg_quote($pattern, '#');

        // **/ matches any directories (including none)
        $regex = str_replace('\\*\\*/', '(.*/)?', $regex);

        // ** matches anything including slashes
        $regex = str_replace('\\*\\*', '.*', $regex);

        // * matches anything except slashes
        $regex = str_replace('\\*', '[^/]*', $regex);

        // ? matches single character
        $regex = str_replace('\\?', '.', $regex);

        return (bool) preg_match('#^' . $regex . '$#', $path);
    }
}
