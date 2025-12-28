<?php

namespace AaronFrancis\ImgProxy\Contracts;

/**
 * Contract for custom path validators.
 *
 * Implement this interface to create custom validation logic
 * for restricting which image paths can be proxied.
 */
interface PathValidatorContract
{
    /**
     * Validate whether a path is allowed to be proxied.
     *
     * @param  string  $path  The full path to validate
     * @return bool True if the path is allowed
     */
    public function validate(string $path): bool;
}
