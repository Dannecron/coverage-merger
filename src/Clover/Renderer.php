<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Clover;

use Dannecron\CoverageMerger\Clover\Dto\Accumulator;
use Dannecron\CoverageMerger\Clover\Dto\ClassDto;
use Dannecron\CoverageMerger\Clover\Dto\FileDto;
use Dannecron\CoverageMerger\Clover\Dto\LineDto;
use Dannecron\CoverageMerger\Clover\Dto\Metrics;

class Renderer
{
    /**
     * @param Accumulator $accumulator
     * @return array{0: string, 1: Metrics}
     * @throws \DOMException
     */
    public function renderAccumulator(Accumulator $accumulator): array
    {
        $files = $accumulator->getFiles();
        \ksort($files);

        $xmlDocument = new \DOMDocument('1.0', 'UTF-8');

        $xmlCoverage = $xmlDocument->createElement('coverage');
        $xmlCoverage->setAttribute('generated', (string)\time());
        $xmlDocument->appendChild($xmlCoverage);

        $xmlProject = $xmlDocument->createElement('project');
        $xmlProject->setAttribute('timestamp', (string)\time());
        $xmlCoverage->appendChild($xmlProject);

        $projectMetrics = Metrics::makeEmpty();
        $packages = [];

        foreach ($files as $name => $file) {
            [$xmlFile, $fileMetrics] = $this->renderFile($xmlDocument, $file, $name);
            $projectMetrics = $projectMetrics->merge($fileMetrics);
            $packageName = $file->packageName;
            if ($packageName === null) {
                $xmlProject->appendChild($xmlFile);
                continue;
            }

            $existedPackage = $packages[$packageName] ?? null;
            if ($existedPackage !== null) {
                $existedPackage[0]->appendChild($xmlFile);
                $existedPackage[1] = $existedPackage[1]->merge($fileMetrics);
                $packages[$packageName] = $existedPackage;
                continue;
            }

            $xmlPackage = $xmlDocument->createElement('package');
            $xmlPackage->setAttribute('name', $packageName);
            $xmlProject->appendChild($xmlPackage);
            $xmlPackage->appendChild($xmlFile);

            $packageMetrics = Metrics::makeEmpty();
            $packageMetrics->packageCount = 1;
            $packageMetrics = $packageMetrics->merge($fileMetrics);
            $packages[$packageName] = [$xmlPackage, $packageMetrics];
        }

        foreach ($packages as $package) {
            /** @var Metrics $packageMetrics */
            $packageMetrics = $package[1];
            $package[0]->appendChild($this->renderMetricsPackage($xmlDocument, $packageMetrics));
        }

        $xmlProject->appendChild($this->renderMetricsProject($xmlDocument, $projectMetrics));

        return [(string) $xmlDocument->saveXML(), $projectMetrics];
    }

    /**
     * Create an XML element to represent these metrics under a file.
     * @param \DOMDocument $xmlDocument The parent document
     * @param Metrics $metrics
     * @return \DOMElement
     * @throws \DOMException
     */
    private function renderMetrics(\DOMDocument $xmlDocument, Metrics $metrics): \DOMElement
    {
        $xmlMetrics = $xmlDocument->createElement('metrics');
        // We can't know the complexity, just set 0
        // (attribute required by the clover xml schema)
        $xmlMetrics->setAttribute('complexity', '0');

        $xmlMetrics->setAttribute('elements', (string) $metrics->getElementCount());
        $xmlMetrics->setAttribute('coveredelements', (string) $metrics->getCoveredElementCount());
        $xmlMetrics->setAttribute('conditionals', (string) $metrics->conditionalCount);
        $xmlMetrics->setAttribute('coveredconditionals', (string) $metrics->coveredConditionalCount);
        $xmlMetrics->setAttribute('statements', (string) $metrics->statementCount);
        $xmlMetrics->setAttribute('coveredstatements', (string) $metrics->coveredStatementCount);
        $xmlMetrics->setAttribute('methods', (string) $metrics->methodCount);
        $xmlMetrics->setAttribute('coveredmethods', (string) $metrics->coveredMethodCount);
        $xmlMetrics->setAttribute('classes', (string) $metrics->classCount);

        return $xmlMetrics;
    }

    /**
     * Create an XML element to represent these metrics under a package.
     * Contains all the attributes of the file context plus the number of files.
     * @param \DOMDocument $xmlDocument The parent document.
     * @param Metrics $metrics
     * @return \DOMElement
     * @throws \DOMException
     */
    private function renderMetricsPackage(\DOMDocument $xmlDocument, Metrics $metrics): \DOMElement
    {
        $xmlMetrics = $this->renderMetrics($xmlDocument, $metrics);
        $xmlMetrics->setAttribute('files', (string) $metrics->fileCount);

        return $xmlMetrics;
    }

    /**
     * Create an XML element to represent these metrics under a project.
     * Contains all the attributes of the package context plus the number of packages.
     * @param \DOMDocument $xmlDocument The parent document.
     * @param Metrics $metrics
     * @return \DOMElement
     * @throws \DOMException
     */
    private function renderMetricsProject(\DOMDocument $xmlDocument, Metrics $metrics): \DOMElement
    {
        $xmlMetrics = $this->renderMetricsPackage($xmlDocument, $metrics);
        $xmlMetrics->setAttribute('packages', (string) $metrics->packageCount);

        return $xmlMetrics;
    }

    /**
     * @param \DOMDocument $document
     * @param FileDto $fileDto
     * @param string $name
     * @return array{0:\DOMElement,1:Metrics}
     * @throws \DOMException
     */
    private function renderFile(\DOMDocument $document, FileDto $fileDto, string $name): array
    {
        $xmlFile = $document->createElement('file');
        $xmlFile->setAttribute('name', $name);

        $classes = $fileDto->getClasses();
        $lines = $fileDto->getLines();

        // Metric counts
        $statementCount = 0;
        $coveredStatementCount = 0;
        $conditionalCount = 0;
        $coveredConditionalCount = 0;
        $methodCount = 0;
        $coveredMethodCount = 0;
        $classCount = \count($classes);

        foreach ($classes as $class) {
            $xmlFile->appendChild($this->renderClass($document, $class));
        }

        foreach ($lines as $line) {
            $xmlFile->appendChild($this->renderLine($document, $line));
            $properties = $line->getProperties();

            $covered = $line->getCount() > 0;
            $type = $properties['type'] ?? 'stmt';

            if ($type === 'method') {
                $methodCount++;
                if ($covered) {
                    $coveredMethodCount++;
                }
            } elseif ($type === 'stmt') {
                $statementCount++;
                if ($covered) {
                    $coveredStatementCount++;
                }
            } elseif ($type === 'cond') {
                $conditionalCount++;
                if ($covered) {
                    $coveredConditionalCount++;
                }
            }
        }

        $metrics = new Metrics(
            $statementCount,
            $coveredStatementCount,
            $conditionalCount,
            $coveredConditionalCount,
            $methodCount,
            $coveredMethodCount,
            $classCount,
            1
        );

        $xmlFile->appendChild($this->renderMetrics($document, $metrics));

        return [$xmlFile, $metrics];
    }

    /**
     * @param \DOMDocument $document
     * @param ClassDto $class
     * @return \DOMElement
     * @throws \DOMException
     */
    private function renderClass(\DOMDocument $document, ClassDto $class): \DOMElement
    {
        $xmlClass = $document->createElement('class');

        foreach ($class->getProperties() as $key => $value) {
            $xmlClass->setAttribute($key, $value);
        }

        return $xmlClass;
    }

    /**
     * @param \DOMDocument $document
     * @param LineDto $line
     * @return \DOMElement
     * @throws \DOMException
     */
    private function renderLine(\DOMDocument $document, LineDto $line): \DOMElement
    {
        $xmlLine = $document->createElement('line');
        foreach ($line->getProperties() as $key => $value) {
            $xmlLine->setAttribute($key, $value);
        }

        $xmlLine->setAttribute('count', (string) $line->getCount());

        return $xmlLine;
    }
}
