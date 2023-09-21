<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Clover\Dto;

class FileDto
{
    /** @var array<string, ClassDto>  */
    private array $classes;
    /** @var array<int, LineDto>  */
    private array $lines;

    public function __construct(
        public readonly ?string $packageName = null,
    ) {
        $this->classes = [];
        $this->lines = [];
    }

    /**
     * @return array<string, ClassDto>
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @return array<int, LineDto>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function hasClass(string $name): bool
    {
        return (bool) ($this->classes[$name] ?? false);
    }

    public function hasLine(int $number): bool
    {
        return (bool) ($this->lines[$number] ?? false);
    }

    public function mergeFile(FileDto $otherFile): self
    {
        $mergedClasses = \array_merge($otherFile->getClasses(), $this->classes);
        $this->classes = $mergedClasses;

        foreach ($otherFile->getLines() as $number => $line) {
            $this->mergeLine($number, $line);
        }

        return $this;
    }

    public function mergeClass(string $name, ClassDto $class): self
    {
        if ($this->hasClass($name) === false) {
            $this->classes[$name] = $class;
        }

        return $this;
    }

    public function mergeLine(int $number, LineDto $line): self
    {
        if ($this->hasLine($number) === false) {
            $this->lines[$number] = $line;

            return $this;
        }

        $existedLine = $this->lines[$number];
        $existedLine->merge($line);

        return $this;
    }
}
