<?php

namespace AaronFrancis\ImgProxy;

use AaronFrancis\ImgProxy\Contracts\PathValidatorContract;

/**
 * Fluent builder for creating path validation rules.
 *
 * Allows combining multiple constraints (directories, patterns, extensions)
 * that are evaluated using AND logic - all configured constraints must pass.
 */
class PathValidatorBuilder implements PathValidatorContract
{
    protected array $directories = [];

    protected array $patterns = [];

    protected array $extensions = [];

    /**
     * Validate a path against all configured constraints.
     *
     * Uses AND logic: if directories, patterns, and extensions are all configured,
     * the path must satisfy at least one item from each category.
     *
     * @param  string  $path  The path to validate
     * @return bool  True if path passes all constraints
     */
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

    public function directories(array $directories): static
    {
        $this->directories = array_merge($this->directories, $directories);

        return $this;
    }

    public function matches(array $patterns): static
    {
        $this->patterns = array_merge($this->patterns, $patterns);

        return $this;
    }

    public function extensions(array $extensions): static
    {
        $this->extensions = array_merge($this->extensions, $extensions);

        return $this;
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
     * Match a path against a glob-style pattern.
     *
     * Supported patterns:
     *   - ** /  matches any directories (including none)
     *   - **   matches anything including slashes
     *   - *    matches anything except slashes
     *   - ?    matches a single character
     *
     * @param  string  $path  The path to check
     * @param  string  $pattern  The glob pattern
     * @return bool  True if the path matches the pattern
     */
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
