<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter\Tests\Support;

use Closure;
use Michaeld555\PdfConverter\Contract\ProcessRunner;
use Michaeld555\PdfConverter\Process\ProcessResult;

final class FakeProcessRunner implements ProcessRunner
{
    /**
     * @var list<array{command: non-empty-list<string>, timeout: float}>
     */
    public array $calls = [];

    /**
     * @param Closure(non-empty-list<string>, float): ProcessResult $callback
     */
    public function __construct(private readonly Closure $callback) {}

    public function run(array $command, float $timeout): ProcessResult
    {
        $this->calls[] = [
            'command' => $command,
            'timeout' => $timeout,
        ];

        return ($this->callback)($command, $timeout);
    }
}
