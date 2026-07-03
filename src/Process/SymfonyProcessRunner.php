<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter\Process;

use Michaeld555\PdfConverter\Contract\ProcessRunner;
use Symfony\Component\Process\Process;

final class SymfonyProcessRunner implements ProcessRunner
{
    public function run(array $command, float $timeout): ProcessResult
    {
        $process = new Process($command);
        $process->setTimeout($timeout);
        $exitCode = $process->run();

        return new ProcessResult(
            $exitCode,
            $process->getOutput(),
            $process->getErrorOutput(),
        );
    }
}
