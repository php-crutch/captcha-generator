<?php

declare(strict_types=1);

namespace Crutch\CaptchaGenerator\Command;

abstract class AbstractCommand
{
    final public function __construct()
    {
    }

    abstract public static function getName(): string;

    /**
     * @return array<string, string>
     */
    abstract public static function getArguments(): array;

    /**
     * @return array<string, string>
     */
    abstract public static function getFlags(): array;

    /**
     * @return array<string, string>
     */
    abstract public static function getOptions(): array;

    abstract public static function getDescription(): string;

    abstract public function execute(array $arguments, array $options, array $flags): int;
}
