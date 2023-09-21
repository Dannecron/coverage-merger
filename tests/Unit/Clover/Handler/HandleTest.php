<?php

declare(strict_types=1);

use Dannecron\CoverageMerger\Clover\Dto\ClassDto;
use Dannecron\CoverageMerger\Clover\Dto\FileDto;
use Dannecron\CoverageMerger\Clover\Dto\LineDto;
use Dannecron\CoverageMerger\Clover\Handler;
use Dannecron\CoverageMerger\Clover\Parser;

\test('merge multiple valid files', function (): void {
    $fileWithPackage = \file_get_contents(\getExamplePath('file-with-package.xml'));
    $fileWithoutPackage = \file_get_contents(\getExamplePath('file-without-package.xml'));
    $fileWithDifferences = \file_get_contents(\getExamplePath('file-with-differences.xml'));
    $metricsAndClasses = \file_get_contents(\getExamplePath('metrics-and-classes.xml'));

    $handler = new Handler(new Parser());

    $accumulator = $handler->handle(
        \simplexml_load_string($fileWithPackage),
        \simplexml_load_string($fileWithoutPackage),
        \simplexml_load_string($fileWithDifferences),
        \simplexml_load_string($metricsAndClasses),
    );

    $files = $accumulator->getFiles();
    \expect($files)->toHaveCount(3)
        ->toHaveKey('test.php')
        ->toHaveKey('other.php')
        ->toHaveKey('/src/Example/Namespace/Class.php')
        ->each->toBeInstanceOf(FileDto::class);

    $testFile = $files['test.php'];
    \expect($testFile->getClasses())->toHaveCount(0);
    $testFileLines = $testFile->getLines();
    \expect($testFileLines)->toHaveCount(7)
        ->toHaveKeys([1, 2, 3, 4, 5, 6, 8])
        ->each->toBeInstanceOf(LineDto::class)
        ->toMatchCallback(fn (LineDto $line): bool => match ($line->getNum()) {
            1 => $line->getCount() === 0,
            2, 8 => $line->getCount() === 3,
            3, 5 => $line->getCount() === 4,
            6 => $line->getCount() === 1,
            4 => $line->getCount() === 9,
            default => true,
        });

    $classFile = $files['/src/Example/Namespace/Class.php'];
    \expect($classFile->getClasses())->toHaveCount(1)
        ->each->toBeInstanceOf(ClassDto::class)
        ->toMatchCallback(function (ClassDto $class): bool {
            $properties = $class->getProperties();

            return $properties['name'] === 'Example\Namespace\Class'
                && $properties['namespace'] === 'Example\Namespace';
        });
});

// todo merge multiple files with empty report
// todo merge multiple files with invalid report
