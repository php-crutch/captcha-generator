<?php

declare(strict_types=1);

namespace Crutch\CaptchaGenerator\Exception;

use InvalidArgumentException;

final class UnsupportedCharacters extends InvalidArgumentException
{
    private string $allowedCharacters;

    public function __construct(string $allowedCharacters)
    {
        $this->allowedCharacters = $allowedCharacters;
        parent::__construct('text contains unsupported characters', 1);
    }

    public function getAllowedCharacters(): string
    {
        return $this->allowedCharacters;
    }
}
