<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter\Contract;

use Michaeld555\PdfConverter\Exception\ConversionException;

interface DocumentConverter
{
    /**
     * Convert a local DOCX file into a PDF file.
     *
     * @throws ConversionException
     */
    public function convert(string $source, string $destination): void;
}
