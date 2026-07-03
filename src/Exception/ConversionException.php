<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter\Exception;

use RuntimeException;
use Throwable;

final class ConversionException extends RuntimeException
{
    public static function sourceDoesNotExist(string $source): self
    {
        return new self(\sprintf('The source file does not exist or is not a regular file: "%s".', $source));
    }

    public static function sourceIsNotReadable(string $source): self
    {
        return new self(\sprintf('The source file is not readable: "%s".', $source));
    }

    public static function unsupportedSource(string $source): self
    {
        return new self(\sprintf('Only local DOCX files are supported; received: "%s".', $source));
    }

    public static function invalidDestination(string $destination, string $reason): self
    {
        return new self(\sprintf('Invalid destination "%s": %s.', $destination, $reason));
    }

    public static function destinationExists(string $destination): self
    {
        return new self(\sprintf(
            'The destination file already exists: "%s". Enable overwrite to replace it.',
            $destination,
        ));
    }

    public static function temporaryDirectory(string $directory): self
    {
        return new self(\sprintf('Unable to create or use the temporary directory: "%s".', $directory));
    }

    public static function processCouldNotStart(string $binary, Throwable $previous): self
    {
        return new self(
            \sprintf('Unable to execute LibreOffice using "%s": %s', $binary, $previous->getMessage()),
            0,
            $previous,
        );
    }

    public static function processFailed(int $exitCode, string $details): self
    {
        return new self(\sprintf(
            'LibreOffice conversion failed with exit code %d.%s',
            $exitCode,
            '' === $details ? '' : \sprintf(' Details: %s', $details),
        ));
    }

    public static function outputWasNotGenerated(string $expectedFile): self
    {
        return new self(\sprintf(
            'LibreOffice reported success but did not generate the expected PDF: "%s".',
            $expectedFile,
        ));
    }

    public static function invalidPdf(string $file): self
    {
        return new self(\sprintf('The generated file is empty or does not contain a valid PDF header: "%s".', $file));
    }

    public static function outputCouldNotBeSaved(string $destination): self
    {
        return new self(\sprintf('The generated PDF could not be saved to: "%s".', $destination));
    }
}
