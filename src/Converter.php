<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter;

use Michaeld555\PdfConverter\Contract\DocumentConverter;
use Michaeld555\PdfConverter\Driver\LibreOfficeConverter;

/**
 * Public facade for document conversion.
 */
final class Converter implements DocumentConverter
{
    private readonly DocumentConverter $driver;

    public function __construct(?DocumentConverter $driver = null)
    {
        $this->driver = $driver ?? new LibreOfficeConverter();
    }

    public function convert(string $source, string $destination): void
    {
        $this->driver->convert($source, $destination);
    }
}
