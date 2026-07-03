<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter\Driver;

use FilesystemIterator;
use Michaeld555\PdfConverter\Contract\DocumentConverter;
use Michaeld555\PdfConverter\Contract\ProcessRunner;
use Michaeld555\PdfConverter\Exception\ConversionException;
use Michaeld555\PdfConverter\Process\SymfonyProcessRunner;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

final class LibreOfficeConverter implements DocumentConverter
{
    private readonly ProcessRunner $processRunner;

    private readonly string $temporaryDirectory;

    public function __construct(
        private readonly string $binary = 'libreoffice',
        private readonly float $timeout = 60.0,
        private readonly bool $overwrite = false,
        ?ProcessRunner $processRunner = null,
        ?string $temporaryDirectory = null,
    ) {
        if ('' === trim($this->binary)) {
            throw new \InvalidArgumentException('The LibreOffice binary cannot be empty.');
        }

        if ($this->timeout <= 0) {
            throw new \InvalidArgumentException('The conversion timeout must be greater than zero.');
        }

        $this->processRunner = $processRunner ?? new SymfonyProcessRunner();
        $this->temporaryDirectory = $temporaryDirectory ?? sys_get_temp_dir();
    }

    public function convert(string $source, string $destination): void
    {
        $source = $this->validateSource($source);
        $destination = $this->validateDestination($destination);
        $workingDirectory = $this->createWorkingDirectory();

        try {
            $generatedPdf = $this->runConversion($source, $workingDirectory);
            $this->assertValidPdf($generatedPdf);
            $this->savePdf($generatedPdf, $destination);
        } finally {
            $this->removeDirectory($workingDirectory);
        }
    }

    private function validateSource(string $source): string
    {
        if ('' === trim($source) || str_contains($source, '://')) {
            throw ConversionException::unsupportedSource($source);
        }

        $realSource = realpath($source);

        if (false === $realSource || !is_file($realSource)) {
            throw ConversionException::sourceDoesNotExist($source);
        }

        if (!is_readable($realSource)) {
            throw ConversionException::sourceIsNotReadable($realSource);
        }

        if ('docx' !== strtolower((string) pathinfo($realSource, PATHINFO_EXTENSION))) {
            throw ConversionException::unsupportedSource($realSource);
        }

        return $realSource;
    }

    private function validateDestination(string $destination): string
    {
        if (
            '' === trim($destination)
            || str_ends_with($destination, '/')
            || str_ends_with($destination, '\\')
        ) {
            throw ConversionException::invalidDestination($destination, 'provide the complete PDF filename');
        }

        if ('pdf' !== strtolower((string) pathinfo($destination, PATHINFO_EXTENSION))) {
            throw ConversionException::invalidDestination($destination, 'the filename must use the .pdf extension');
        }

        if (is_dir($destination)) {
            throw ConversionException::invalidDestination($destination, 'the path points to a directory');
        }

        $parent = \dirname($destination);
        $realParent = realpath($parent);

        if (false === $realParent || !is_dir($realParent)) {
            throw ConversionException::invalidDestination($destination, 'the parent directory does not exist');
        }

        if (!is_writable($realParent)) {
            throw ConversionException::invalidDestination($destination, 'the parent directory is not writable');
        }

        $destination = $realParent . DIRECTORY_SEPARATOR . basename($destination);

        if (file_exists($destination) && !$this->overwrite) {
            throw ConversionException::destinationExists($destination);
        }

        return $destination;
    }

    private function createWorkingDirectory(): string
    {
        $baseDirectory = realpath($this->temporaryDirectory);

        if (false === $baseDirectory || !is_dir($baseDirectory) || !is_writable($baseDirectory)) {
            throw ConversionException::temporaryDirectory($this->temporaryDirectory);
        }

        for ($attempt = 0; $attempt < 5; ++$attempt) {
            try {
                $directory = $baseDirectory . DIRECTORY_SEPARATOR . 'pdf-converter-' . bin2hex(random_bytes(12));
            } catch (Throwable) {
                throw ConversionException::temporaryDirectory($baseDirectory);
            }

            if (@mkdir($directory, 0o700)) {
                return $directory;
            }
        }

        throw ConversionException::temporaryDirectory($baseDirectory);
    }

    private function runConversion(string $source, string $workingDirectory): string
    {
        $outputDirectory = $workingDirectory . DIRECTORY_SEPARATOR . 'output';
        $profileDirectory = $workingDirectory . DIRECTORY_SEPARATOR . 'profile';

        if (!mkdir($outputDirectory, 0o700) || !mkdir($profileDirectory, 0o700)) {
            throw ConversionException::temporaryDirectory($workingDirectory);
        }

        $command = [
            $this->binary,
            '-env:UserInstallation=' . $this->toFileUri($profileDirectory),
            '--headless',
            '--nologo',
            '--nodefault',
            '--nolockcheck',
            '--nofirststartwizard',
            '--convert-to',
            'pdf:writer_pdf_Export',
            '--outdir',
            $outputDirectory,
            $source,
        ];

        try {
            $result = $this->processRunner->run($command, $this->timeout);
        } catch (Throwable $exception) {
            throw ConversionException::processCouldNotStart($this->binary, $exception);
        }

        if (!$result->isSuccessful()) {
            $details = trim($result->errorOutput . "\n" . $result->standardOutput);
            throw ConversionException::processFailed($result->exitCode, $details);
        }

        $generatedPdf = $outputDirectory . DIRECTORY_SEPARATOR . pathinfo($source, PATHINFO_FILENAME) . '.pdf';

        if (!is_file($generatedPdf)) {
            throw ConversionException::outputWasNotGenerated($generatedPdf);
        }

        return $generatedPdf;
    }

    private function assertValidPdf(string $file): void
    {
        $handle = @fopen($file, 'rb');

        if (false === $handle) {
            throw ConversionException::invalidPdf($file);
        }

        try {
            $header = fread($handle, 5);
        } finally {
            fclose($handle);
        }

        if ('%PDF-' !== $header || 5 >= (filesize($file) ?: 0)) {
            throw ConversionException::invalidPdf($file);
        }
    }

    private function savePdf(string $generatedPdf, string $destination): void
    {
        if (file_exists($destination) && !$this->overwrite) {
            throw ConversionException::destinationExists($destination);
        }

        try {
            $suffix = bin2hex(random_bytes(8));
        } catch (Throwable) {
            throw ConversionException::outputCouldNotBeSaved($destination);
        }

        $temporaryDestination = \dirname($destination)
            . DIRECTORY_SEPARATOR
            . '.' . basename($destination) . '.' . $suffix . '.tmp';

        if (!@copy($generatedPdf, $temporaryDestination)) {
            @unlink($temporaryDestination);
            throw ConversionException::outputCouldNotBeSaved($destination);
        }

        if (file_exists($destination) && !$this->overwrite) {
            @unlink($temporaryDestination);
            throw ConversionException::destinationExists($destination);
        }

        if (@rename($temporaryDestination, $destination)) {
            return;
        }

        if ($this->overwrite && file_exists($destination)) {
            @unlink($destination);
        }

        if (!@rename($temporaryDestination, $destination)) {
            @unlink($temporaryDestination);
            throw ConversionException::outputCouldNotBeSaved($destination);
        }
    }

    private function toFileUri(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $segments = explode('/', $normalizedPath);

        foreach ($segments as $index => $segment) {
            if (0 === $index && preg_match('/^[A-Za-z]:$/', $segment)) {
                continue;
            }

            $segments[$index] = rawurlencode($segment);
        }

        $encodedPath = implode('/', $segments);

        return str_starts_with($encodedPath, '/')
            ? 'file://' . $encodedPath
            : 'file:///' . $encodedPath;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($iterator as $item) {
                if (!$item instanceof SplFileInfo) {
                    continue;
                }

                if ($item->isDir() && !$item->isLink()) {
                    @rmdir($item->getPathname());
                } else {
                    @unlink($item->getPathname());
                }
            }
        } catch (Throwable) {
            // Cleanup must not hide the original conversion result or exception.
        }

        @rmdir($directory);
    }
}
