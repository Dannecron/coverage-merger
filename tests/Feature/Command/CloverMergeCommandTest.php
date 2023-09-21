<?php

declare(strict_types=1);

use Ahc\Cli\IO\Interactor;
use Ahc\Cli\Output\Writer;
use Dannecron\CoverageMerger\Clover\Handler;
use Dannecron\CoverageMerger\Clover\Parser;
use Dannecron\CoverageMerger\Clover\Renderer;
use Dannecron\CoverageMerger\Command\CloverMergeCommand;
use Dannecron\CoverageMerger\Command\Exceptions\ExecuteException;
use Dannecron\CoverageMerger\Command\Exceptions\InvalidArgumentException;
use Tests\Helpers\Traits\MakeCliApplication;

\uses(MakeCliApplication::class);

\beforeEach(function (): void {
    $mergedPath = '/tmp/merged.xml';
    if (\is_file($mergedPath)) {
        \unlink($mergedPath);
    }
});

\test('merge two files no workdir', function (): void {
    $cliApp = $this->makeCliApp();
    $cliApp->add(
        new CloverMergeCommand(
            new Handler(new Parser()),
            new Renderer(),
            $cliApp,
        ),
    );

    $mergedPath = '/tmp/merged.xml';
    \expect($mergedPath)->not->toBeFile();

    $cliApp->handle([
        './merger',
        'clover',
        '-o',
        $mergedPath,
        \getExamplePath('metrics-and-classes.xml'),
        \getExamplePath('file-with-differences.xml'),
    ]);

    \expect($mergedPath)->toBeFile()->toBeReadableFile();
    \expect(\file_get_contents($mergedPath))->toBeString()
        ->not->toBeEmpty();
});

\test('merge two files with workdir', function (): void {
    $cliApp = $this->makeCliApp();
    $cliApp->add(
        new CloverMergeCommand(
            new Handler(new Parser()),
            new Renderer(),
            $cliApp,
        ),
    );

    $filename1 = 'metrics-and-classes.xml';
    $filename2 = 'file-with-differences.xml';

    $dir = '/tmp/temp_source';
    if (\is_dir($dir) === false) {
        \mkdir($dir);
    }
    \copy(\getExamplePath($filename1), "{$dir}/{$filename1}");
    \copy(\getExamplePath($filename2), "{$dir}/{$filename2}");

    $mergedPath = 'some_merged.xml';
    $mergedFullPath = "{$dir}/{$mergedPath}";
    \expect($mergedFullPath)->not->toBeFile();

    $cliApp->handle([
        './merger',
        'clover',
        '-o',
        $mergedPath,
        '-w',
        $dir,
        $filename1,
        $filename2,
    ]);

    \expect($mergedFullPath)->toBeFile()->toBeReadableFile();
    \expect(\file_get_contents($mergedFullPath))->toBeString()
        ->not->toBeEmpty();

    \unlink($mergedFullPath);
});

\test('merge two files no workdir with stats', function (): void {
    $cliApp = $this->makeCliApp();

    $interactorMock = \Mockery::mock(Interactor::class);
    $interactorMock->shouldReceive('info')
        ->once()
        ->with(
            'Files Discovered: 2',
            true
        )
        ->andReturn(\Mockery::mock(Writer::class));
    $interactorMock->shouldReceive('info')
        ->once()
        ->with(
            'Final Coverage: 5/5 (100.00%)',
            true
        )
        ->andReturn(\Mockery::mock(Writer::class));

    $cliApp->io($interactorMock);
    $cliApp->add(
        new CloverMergeCommand(
            new Handler(new Parser()),
            new Renderer(),
            $cliApp,
        ),
    );

    $mergedPath = '/tmp/merged.xml';
    \expect($mergedPath)->not->toBeFile();

    $cliApp->handle([
        './merger',
        'clover',
        '-o',
        $mergedPath,
        '-s',
        \getExamplePath('empty-package.xml'),
        \getExamplePath('file-with-differences.xml'),
    ]);

    \expect($mergedPath)->toBeFile()->toBeReadableFile();
    \expect(\file_get_contents($mergedPath))->toBeString()
        ->not->toBeEmpty();
});

\test('merge two files error file not exist', function (): void {
    $cliApp = $this->makeCliApp();
    $cliApp->add(
        new CloverMergeCommand(
            new Handler(new Parser()),
            new Renderer(),
            $cliApp,
        ),
    );

    $mergedPath = '/tmp/merged.xml';
    \expect($mergedPath)->not->toBeFile();

    $badFilePath = \getExamplePath('some-very-bad-file.xml');

    $this->expectException(ExecuteException::class);
    $this->expectExceptionMessage("File {$badFilePath} does not exists or not readable");

    $cliApp->handle([
        './merger',
        'clover',
        '-o',
        $mergedPath,
        \getExamplePath('metrics-and-classes.xml'),
        $badFilePath,
    ]);
});

\test('merge two files error file not xml', function (): void {
    $cliApp = $this->makeCliApp();
    $cliApp->add(
        new CloverMergeCommand(
            new Handler(new Parser()),
            new Renderer(),
            $cliApp,
        ),
    );

    $mergedPath = '/tmp/merged.xml';
    \expect($mergedPath)->not->toBeFile();

    $badFilePath = \getExamplePath('not-xml.json');

    $this->expectException(ExecuteException::class);
    $this->expectExceptionMessage("Unable to parse file {$badFilePath}");

    $cliApp->handle([
        './merger',
        'clover',
        '-o',
        $mergedPath,
        \getExamplePath('metrics-and-classes.xml'),
        $badFilePath,
    ]);
});

\test('merge two files error workdir not exist', function (): void {
    $cliApp = $this->makeCliApp();
    $cliApp->add(
        new CloverMergeCommand(
            new Handler(new Parser()),
            new Renderer(),
            $cliApp,
        ),
    );

    $mergedPath = 'tmp/merged.xml';
    \expect($mergedPath)->not->toBeFile();

    $badFilePath = \getExamplePath('some-very-bad-file.xml');

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid argument workdir');

    $cliApp->handle([
        './merger',
        'clover',
        '-o',
        $mergedPath,
        '-w',
        '/tmp/foo/bar/baz',
        \getExamplePath('metrics-and-classes.xml'),
        \getExamplePath('file-with-differences.xml'),
    ]);
});

\test('merge two files error no arguments', function (): void {
    $cliApp = $this->makeCliApp();
    $cliApp->add(
        new CloverMergeCommand(
            new Handler(new Parser()),
            new Renderer(),
            $cliApp,
        ),
    );

    $mergedPath = '/tmp/merged.xml';
    \expect($mergedPath)->not->toBeFile();

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid argument files');

    $cliApp->handle([
        './merger',
        'clover',
        '-o',
        $mergedPath,
    ]);
});

\test('merge two files error in handler', function (): void {
    $cliApp = $this->makeCliApp();

    $handlerMock = \Mockery::mock(Handler::class);
    $handlerMock->shouldReceive('handle')
        ->once()
        ->with(
            \Mockery::type(\SimpleXMLElement::class),
            \Mockery::type(\SimpleXMLElement::class),
        )
        ->andThrow(new \RuntimeException('some error'));

    $cliApp->add(
        new CloverMergeCommand(
            $handlerMock,
            new Renderer(),
            $cliApp,
        ),
    );

    $mergedPath = '/tmp/merged.xml';
    \expect($mergedPath)->not->toBeFile();

    $this->expectException(ExecuteException::class);
    $this->expectExceptionMessage('some error');

    $cliApp->handle([
        './merger',
        'clover',
        '-o',
        $mergedPath,
        \getExamplePath('metrics-and-classes.xml'),
        \getExamplePath('file-with-differences.xml'),
    ]);
});
