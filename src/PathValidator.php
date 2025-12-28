<?php

namespace AaronFrancis\ImgProxy;

/**
 * @method static PathValidatorBuilder directories(array $directories)
 * @method static PathValidatorBuilder matches(array $patterns)
 * @method static PathValidatorBuilder extensions(array $extensions)
 */
class PathValidator
{
    public static function __callStatic(string $method, array $arguments): PathValidatorBuilder
    {
        return (new PathValidatorBuilder)->{$method}(...$arguments);
    }
}
