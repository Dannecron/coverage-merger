<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Clover\Dto;

class LineDto
{
    /**
     * @param array<string, string> $properties Other properties on the line.
     *                               E.g. name, visibility, complexity, crap
     * @param int $count Number of hits on this line
     */
    public function __construct(
        private array $properties,
        private int $count,
    ) {
    }

    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return array<string, string>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getNum(): ?int
    {
        return isset($this->properties['num'])
            ? (int) $this->properties['num']
            : null;
    }

    public function merge(LineDto $otherLine): self
    {
        $this->properties = \array_merge($otherLine->getProperties(), $this->properties);
        $this->count += $otherLine->getCount();

        return $this;
    }
}
