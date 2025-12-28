<?php

namespace AaronFrancis\ImgProxy\Contracts;

interface PathValidatorContract
{
    public function validate(string $path): bool;
}
