<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Command\Exceptions;

class InvalidArgumentException extends CommandException
{
    public const CODE = 1;

    public function __construct(string $argument, bool $isVerbose = false, ?\Throwable $previous = null)
    {
        parent::__construct("Invalid argument {$argument}", self::CODE, $isVerbose, $previous);
    }
}
