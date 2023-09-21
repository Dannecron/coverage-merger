<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Clover\Dto;

class Metrics
{
    public function __construct(
        public readonly int $statementCount,
        public readonly int $coveredStatementCount,
        public readonly int $conditionalCount,
        public readonly int $coveredConditionalCount,
        public readonly int $methodCount,
        public readonly int $coveredMethodCount,
        public readonly int $classCount,
        public readonly int $fileCount = 0,
        public int $packageCount = 0,
    ) {
    }

    /**
     * Return the number of elements
     * @return int
     */
    public function getElementCount(): int
    {
        return $this->statementCount
            + $this->conditionalCount
            + $this->methodCount;
    }

    /**
     * Return the number of covered elements.
     * @return int
     */
    public function getCoveredElementCount(): int
    {
        return $this->coveredStatementCount
            + $this->coveredConditionalCount
            + $this->coveredMethodCount;
    }

    /**
     * Merge another set of metrics into new one.
     * @param Metrics $metrics
     * @return Metrics
     */
    public function merge(Metrics $metrics): Metrics
    {
        $statementCount = $this->statementCount + $metrics->statementCount;
        $coveredStatementCount = $this->coveredStatementCount + $metrics->coveredStatementCount;
        $conditionalCount = $this->conditionalCount + $metrics->conditionalCount;
        $coveredConditionalCount = $this->coveredConditionalCount + $metrics->coveredConditionalCount;
        $methodCount = $this->methodCount + $metrics->methodCount;
        $coveredMethodCount = $this->coveredMethodCount + $metrics->coveredMethodCount;
        $classCount = $this->classCount + $metrics->classCount;
        $fileCount = $this->fileCount + $metrics->fileCount;
        $packageCount = $this->packageCount + $metrics->packageCount;

        return new Metrics(
            $statementCount,
            $coveredStatementCount,
            $conditionalCount,
            $coveredConditionalCount,
            $methodCount,
            $coveredMethodCount,
            $classCount,
            $fileCount,
            $packageCount,
        );
    }

    public static function makeEmpty(): self
    {
        return new self(0, 0, 0, 0, 0, 0, 0);
    }
}
