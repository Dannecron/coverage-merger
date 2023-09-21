<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Command;

use Ahc\Cli\Application as App;
use Ahc\Cli\Input\Command as CliCommand;
use Ahc\Cli\IO\Interactor;
use Dannecron\CoverageMerger\Command\Exceptions\ExecuteException;
use Dannecron\CoverageMerger\Command\Exceptions\InvalidArgumentException;
use Dannecron\CoverageMerger\Clover\Handler;
use Dannecron\CoverageMerger\Clover\Renderer;

/**
 * @property-read int $verbosity
 */
final class CloverMergeCommand extends CliCommand
{
    private readonly Handler $handler;
    private readonly Renderer $renderer;

    public function __construct(Handler $handler, Renderer $renderer, ?App $_app = null)
    {
        parent::__construct('clover', 'Merge clover coverage files into single one', false, $_app);

        $this->arguments('[files...]')
            ->option('-w|--workdir', 'Path to workdir, to work with relative paths in files')
            ->option('-o|--output', 'Path to result file', null, './merged.xml')
            ->option('-s|--stats', 'Print calculated statistic', null, false);

        $this->handler = $handler;
        $this->renderer = $renderer;
    }

    /**
     * @param Interactor $io
     * @return void
     * @throws InvalidArgumentException
     */
    public function interact(Interactor $io): void
    {
        $isVerbose = $this->verbosity >= 1;

        $values = $this->values(false);
        $files = $values['files'] ?? null;
        $workdir = $values['workdir'] ?? null;

        if ($files === null || \count($files) === 0) {
            throw new InvalidArgumentException('files', $isVerbose);
        }

        if ($workdir !== null && \is_dir($workdir) === false) {
            throw new InvalidArgumentException('workdir', $isVerbose);
        }
    }

    /**
     * @throws ExecuteException
     */
    public function execute(array $files, string $output, ?string $workdir, bool $stats): int
    {
        $isVerbose = $this->verbosity >= 1;

        $fullOutputPath = $output;
        if ($workdir !== null) {
            $fullOutputPath = "{$workdir}/{$output}";
        }

        $documentsCollection = \array_map(static function (string $path) use ($workdir): \SimpleXMLElement {
            $fullPath = $path;
            if ($workdir !== null) {
                $fullPath = "{$workdir}/{$path}";
            }

            $file = new \SplFileInfo($fullPath);

            if ($file->isFile() === false || $file->isReadable() === false) {
                throw new ExecuteException("File {$fullPath} does not exists or not readable");
            }

            $document = \simplexml_load_file(
                $file->getPathname(),
                \SimpleXMLElement::class,
                LIBXML_NOWARNING | LIBXML_NOERROR,
            );

            if ($document === false) {
                throw new ExecuteException("Unable to parse file {$file->getPathname()}");
            }

            return $document;
        }, $files);

        try {
            $accumulator = $this->handler->handle(...$documentsCollection);
            [$xml, $metrics] = $this->renderer->renderAccumulator($accumulator);
        } catch (\Throwable $exception) {
            throw new ExecuteException($exception->getMessage(), $isVerbose, $exception);
        }

        $writeResult = \file_put_contents($fullOutputPath, $xml);

        if ($writeResult === false) {
            throw new ExecuteException('Unable to write to given output file', $isVerbose);
        }

        if ($stats === false) {
            return 0;
        }

        $filesDiscovered = $metrics->fileCount;
        $elementCount = $metrics->getElementCount();
        $coveredElementCount = $metrics->getCoveredElementCount();

        if ($elementCount === 0) {
            $coveragePercentage = 0;
        } else {
            $coveragePercentage = 100 * $coveredElementCount / $elementCount;
        }

        $io = $this->io();
        $io->info(\sprintf("Files Discovered: %d", $filesDiscovered), true);
        $io->info(
            \sprintf("Final Coverage: %d/%d (%.2f%%)", $coveredElementCount, $elementCount, $coveragePercentage),
            true,
        );

        return 0;
    }
}
