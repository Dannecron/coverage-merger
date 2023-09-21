<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Command\Exceptions;

class ExecuteException extends CommandException
{
    public const CODE = 2;

    public function __construct(string $message, bool $isVerbose = false, ?\Throwable $previous = null)
    {
        parent::__construct($message, self::CODE, $isVerbose, $previous);
    }
}
