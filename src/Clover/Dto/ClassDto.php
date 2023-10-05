<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Clover\Dto;

class ClassDto
{
    /**
     * @param array<string, string> $properties
     */
    public function __construct(
        private readonly array $properties,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
