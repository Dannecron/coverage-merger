<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Clover\Dto;

class Accumulator
{
    /** @var array<string, FileDto> $files */
    private array $files;

    public function __construct()
    {
        $this->files = [];
    }

    /**
     * @return array<string, FileDto>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function addFile(string $fileName, FileDto $file): self
    {
        /** @var FileDto|null $existedFile */
        $existedFile = $this->getFiles()[$fileName] ?? null;

        if ($existedFile !== null) {
            $existedFile->mergeFile($file);

            return $this;
        }

        $merged = \array_merge($this->files, [$fileName => $file]);
        $this->files = $merged;

        return $this;
    }
}
