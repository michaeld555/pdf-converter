<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter\Contract;

use Michaeld555\PdfConverter\Process\ProcessResult;

interface ProcessRunner
{
    /**
     * @param non-empty-list<string> $command
     */
    public function run(array $command, float $timeout): ProcessResult;
}
