<?php

declare(strict_types=1);

use Dannecron\CoverageMerger\Clover\Dto\FileDto;
use Dannecron\CoverageMerger\Clover\Exceptions\HandleException;
use Dannecron\CoverageMerger\Clover\Handler;
use Dannecron\CoverageMerger\Clover\Parser;

\test('examples without files', function (string $exampleFilename): void {
    $handler = new Handler(new Parser());

    $cloverContents = (string) \file_get_contents(\getExamplePath($exampleFilename));
    /** @var \SimpleXMLElement $xml */
    $xml = \simplexml_load_string($cloverContents);

    $accumulator = $handler->handleSingleDocument($xml);

    $files = $accumulator->getFiles();
    \expect($files)->toHaveCount(0);
})->with([
    'empty-package.xml',
    'empty-project.xml',
    'file-with-errors.xml',
    'file-with-no-name.xml',
    'minimal.xml',
]);

\test('examples with single file', function (
    string $exampleFilename,
    string $expectedFilename,
    int $expectedClassesCount,
    int $expectedLinesCount,
): void {
    $handler = new Handler(new Parser());

    $cloverContents = (string) \file_get_contents(\getExamplePath($exampleFilename));
    /** @var \SimpleXMLElement $xml */
    $xml = \simplexml_load_string($cloverContents);

    $accumulator = $handler->handleSingleDocument($xml);

    $files = $accumulator->getFiles();
    \expect($files)->toHaveCount(1)->toHaveKey($expectedFilename);

    $file = $files[$expectedFilename];
    \expect($file)->toBeInstanceOf(FileDto::class);
    \expect($file->getClasses())->toHaveCount($expectedClassesCount);
    \expect($file->getLines())->toHaveCount($expectedLinesCount);
})
    ->with([
        ['empty-file-with-package.xml', 'test.php', 0, 0],
        ['file-with-package.xml', 'test.php', 0, 5],
        ['file-without-package.xml', 'test.php', 0, 4],
        ['metrics-and-classes.xml', '/src/Example/Namespace/Class.php', 1, 16],
    ]);

\test('examples with two files', function (
    string $exampleFilename,
    string $expectedFilename1,
    string $expectedFilename2,
): void {
    $handler = new Handler(new Parser());

    $cloverContents = (string) \file_get_contents(\getExamplePath($exampleFilename));
    /** @var \SimpleXMLElement $xml */
    $xml = \simplexml_load_string($cloverContents);

    $accumulator = $handler->handleSingleDocument($xml);

    $files = $accumulator->getFiles();
    \expect($files)->toHaveCount(2)
        ->toHaveKey($expectedFilename1)
        ->toHaveKey($expectedFilename2);
})
    ->with([
        ['empty-file-without-package.xml', 'test.php', 'other.php'],
        ['file-with-differences.xml', 'test.php', 'other.php'],
    ]);

\test('examples with invalid structure', function (string $exampleFilename): void {
    $handler = new Handler(new Parser());

    $cloverContents = (string) \file_get_contents(\getExamplePath($exampleFilename));
    /** @var \SimpleXMLElement $xml */
    $xml = \simplexml_load_string($cloverContents);

    $this->expectException(HandleException::class);
    $handler->handleSingleDocument($xml);
})
    ->with([
        'file-with-bad-line.xml',
        'file-with-junk.xml',
        'non-clover.xml',
    ]);
