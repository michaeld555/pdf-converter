<?php

declare(strict_types=1);

namespace Michaeld555;

use Michaeld555\PdfConverter\Converter as ModernConverter;
use Michaeld555\PdfConverter\Exception\ConversionException;

/**
 * @deprecated Use Michaeld555\PdfConverter\Converter instead.
 */
final class Converter
{
    /**
     * Backward-compatible entry point for the original package API.
     *
     * Remote URLs are no longer supported. When $output is a directory, the
     * destination filename is derived from the DOCX filename.
     *
     * @deprecated Use (new Michaeld555\PdfConverter\Converter())->convert().
     */
    public static function docx_to_pdf(?string $file, ?string $output): void
    {
        trigger_error(
            'Michaeld555\\Converter::docx_to_pdf() is deprecated; use '
            . 'Michaeld555\\PdfConverter\\Converter::convert() instead.',
            E_USER_DEPRECATED,
        );

        if (null === $file || '' === trim($file)) {
            throw ConversionException::unsupportedSource((string) $file);
        }

        if (null === $output || '' === trim($output)) {
            throw ConversionException::invalidDestination((string) $output, 'provide the complete PDF filename');
        }

        if (is_dir($output)) {
            $filename = pathinfo($file, PATHINFO_FILENAME) . '.pdf';
            $output = rtrim($output, '/\\') . DIRECTORY_SEPARATOR . $filename;
        }

        (new ModernConverter())->convert($file, $output);
    }
}
