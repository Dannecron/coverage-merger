<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Command\Exceptions;

class CommandException extends \Exception
{
    private readonly bool $isVerbose;

    public function __construct(string $message, int $code = 0, bool $isVerbose = false, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->isVerbose = $isVerbose;
    }

    public function isVerbose(): bool
    {
        return $this->isVerbose;
    }
}
