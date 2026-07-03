<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter\Process;

final readonly class ProcessResult
{
    public function __construct(
        public int $exitCode,
        public string $standardOutput,
        public string $errorOutput,
    ) {}

    public function isSuccessful(): bool
    {
        return 0 === $this->exitCode;
    }
}
