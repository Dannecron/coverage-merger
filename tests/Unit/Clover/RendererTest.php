<?php

declare(strict_types=1);

use Dannecron\CoverageMerger\Clover\Dto\Accumulator;
use Dannecron\CoverageMerger\Clover\Dto\ClassDto;
use Dannecron\CoverageMerger\Clover\Dto\FileDto;
use Dannecron\CoverageMerger\Clover\Dto\LineDto;
use Dannecron\CoverageMerger\Clover\Dto\Metrics;
use Dannecron\CoverageMerger\Clover\Renderer;

\test('test render accumulator', function (): void {
    $package = 'package';

    $accumulator = new Accumulator();

    $file1 = new FileDto($package);
    $file1->mergeLine(1, new LineDto(['num' => '1', 'type' => 'stmt'], 2));
    $file1->mergeLine(2, new LineDto(['num' => '2', 'type' => 'stmt'], 3));

    $file2 = new FileDto($package);
    $file2->mergeLine(22, new LineDto([
        'num' => '22',
        'type' => 'method',
        'name' => '__construct',
        'visibility' => 'public',
        'complexity' => '7',
        'crap' => '8.23',
    ], 1));
    $file2->mergeLine(24, new LineDto(['num' => '24', 'type' => 'stmt'], 3));
    $file2->mergeClass('Example\Namespace\Class', new ClassDto([
        'name' => 'Example\Namespace\Class',
        'namespace' => 'Example\Namespace',
    ]));

    $file3 = new FileDto();
    $file3->mergeLine(34, new LineDto(['num' => '34', 'type' => 'cond'], 0));
    $file3->mergeLine(38, new LineDto(['num' => '38', 'type' => 'cond'], 1));

    $accumulator->addFile('test1.php', $file1);
    $accumulator->addFile('test2.php', $file2);
    $accumulator->addFile('test3.php', $file3);

    $renderer = new Renderer();
    $result = $renderer->renderAccumulator($accumulator);

    \expect($result)->not->toBeEmpty()
        ->toHaveCount(2);

    $resultXmlString = $result[0];
    \expect($resultXmlString)->toBeString()
        ->not->toBeEmpty()
        ->toStartWith("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<coverage");

    \expect(\simplexml_load_string($resultXmlString))->toBeInstanceOf(\SimpleXMLElement::class)
        ->toMatchCallback(function (\SimpleXMLElement $actualCoverage) use ($package): bool {
            $packageXpath = "/coverage/project/package[@name=\"{$package}\"]";
            $packageFiles = $actualCoverage->xpath("{$packageXpath}/file");
            \expect($packageFiles)->toBeArray()->toHaveCount(2);

            $nonPackageFiles = $actualCoverage->xpath('/coverage/project/file');
            \expect($nonPackageFiles)->toBeArray()->toHaveCount(1);

            $packageMetrics = $actualCoverage->xpath("{$packageXpath}/metrics")[0];
            \expect($packageMetrics)->toBeInstanceOf(\SimpleXMLElement::class);
            \expect($packageMetrics->attributes())
                ->toMatchCallback(
                    static fn (\SimpleXMLElement $attributes): bool => (int) $attributes->elements === 4
                        && (int) $attributes->coveredelements === 4
                        && (int) $attributes->conditionals === 0
                        && (int) $attributes->coveredconditionals === 0
                        && (int) $attributes->statements === 3
                        && (int) $attributes->coveredstatements === 3
                        && (int) $attributes->methods === 1
                        && (int) $attributes->coveredmethods === 1
                        && (int) $attributes->classes === 1,
                    'invalid package metrics',
                );

            $projectMetrics = $actualCoverage->xpath('/coverage/project/metrics')[0];
            \expect($projectMetrics)->toBeInstanceOf(\SimpleXMLElement::class);
            \expect($projectMetrics->attributes())
                ->toMatchCallback(
                    static fn (\SimpleXMLElement $attributes): bool => (int) $attributes->elements === 6
                        && (int) $attributes->coveredelements === 5
                        && (int) $attributes->conditionals === 2
                        && (int) $attributes->coveredconditionals === 1
                        && (int) $attributes->statements === 3
                        && (int) $attributes->coveredstatements === 3
                        && (int) $attributes->methods === 1
                        && (int) $attributes->coveredmethods === 1
                        && (int) $attributes->classes === 1,
                    'invalid project metrics',
                );

            return true;
        });

    $resultMetrics = $result[1];
    \expect($resultMetrics)->toBeInstanceOf(Metrics::class);
});
