<?php

declare(strict_types=1);

namespace Tests\Helpers\Traits;

use Ahc\Cli\Application;

trait MakeCliApplication
{
    protected function makeCliApp(): Application
    {
        $cliApp = new \Ahc\Cli\Application('test', '0.0.1', static fn () => true);
        $cliApp->onException(static function (\Throwable $exception): void {
            throw $exception;
        });

        return $cliApp;
    }
}
