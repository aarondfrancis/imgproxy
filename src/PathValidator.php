<?php

namespace TryHard\ImageProxy;

class PathValidator
{
    protected array $directories = [];
    protected array $patterns = [];

    public function __invoke(string $disk, string $path): bool
    {
        // Always block directory traversal
        if (str_contains($path, '..')) {
            return false;
        }

        if ($this->directories) {
            foreach ($this->directories as $dir) {
                $dir = rtrim($dir, '/') . '/';
                if (str_starts_with($path, $dir)) {
                    return true;
                }
            }
            return false;
        }

        if ($this->patterns) {
            foreach ($this->patterns as $pattern) {
                if ($this->matchesPattern($path, $pattern)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * Default validator that only blocks directory traversal.
     */
    public static function secure(): static
    {
        return new static;
    }

    /**
     * Only allow paths within the specified directories.
     *
     * @param  string  ...$directories
     * @return static
     */
    public static function directories(string ...$directories): static
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
     *
     * @param  string  ...$patterns
     * @return static
     */
    public static function matches(string ...$patterns): static
    {
        $validator = new static;
        $validator->patterns = $patterns;

        return $validator;
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
