<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter\Tests\Unit;

use Michaeld555\PdfConverter\Driver\LibreOfficeConverter;
use Michaeld555\PdfConverter\Exception\ConversionException;
use Michaeld555\PdfConverter\Process\ProcessResult;
use Michaeld555\PdfConverter\Tests\Support\FakeProcessRunner;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class LibreOfficeConverterTest extends TestCase
{
    private string $testDirectory;

    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->testDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdf-converter-test-' . bin2hex(random_bytes(8));
        $this->temporaryDirectory = $this->testDirectory . DIRECTORY_SEPARATOR . 'temporary';

        self::assertTrue(mkdir($this->testDirectory, 0o700));
        self::assertTrue(mkdir($this->temporaryDirectory, 0o700));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testDirectory);
    }

    public function testItConvertsAValidDocxAndStoresThePdfAtomically(): void
    {
        $source = $this->createSource('document with spaces.docx');
        $destination = $this->testDirectory . DIRECTORY_SEPARATOR . 'renamed-output.pdf';
        $runner = $this->successfulRunner('%PDF-1.7 test document');
        $converter = new LibreOfficeConverter(
            binary: '/usr/bin/libreoffice',
            timeout: 12.5,
            processRunner: $runner,
            temporaryDirectory: $this->temporaryDirectory,
        );

        $converter->convert($source, $destination);

        self::assertFileExists($destination);
        self::assertSame('%PDF-1.7 test document', file_get_contents($destination));
        self::assertCount(1, $runner->calls);
        self::assertSame(12.5, $runner->calls[0]['timeout']);
        self::assertSame('/usr/bin/libreoffice', $runner->calls[0]['command'][0]);
        self::assertContains('pdf:writer_pdf_Export', $runner->calls[0]['command']);
        self::assertContains($source, $runner->calls[0]['command']);
        $this->assertTemporaryDirectoryIsEmpty();
    }

    public function testItRejectsRemoteSources(): void
    {
        $converter = $this->converterThatMustNotRun();

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Only local DOCX files are supported');

        $converter->convert('https://example.com/document.docx', $this->testDirectory . '/output.pdf');
    }

    public function testItRejectsMissingSources(): void
    {
        $converter = $this->converterThatMustNotRun();

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('does not exist');

        $converter->convert($this->testDirectory . '/missing.docx', $this->testDirectory . '/output.pdf');
    }

    public function testItRejectsUnsupportedInputExtensions(): void
    {
        $source = $this->createSource('document.txt');
        $converter = $this->converterThatMustNotRun();

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Only local DOCX files are supported');

        $converter->convert($source, $this->testDirectory . '/output.pdf');
    }

    public function testItRequiresACompletePdfDestinationFilename(): void
    {
        $source = $this->createSource();
        $converter = $this->converterThatMustNotRun();

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('filename must use the .pdf extension');

        $converter->convert($source, $this->testDirectory . '/output');
    }

    public function testItDoesNotOverwriteExistingFilesByDefault(): void
    {
        $source = $this->createSource();
        $destination = $this->testDirectory . '/output.pdf';
        file_put_contents($destination, 'existing content');
        $converter = $this->converterThatMustNotRun();

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('already exists');

        $converter->convert($source, $destination);
    }

    public function testItCanOverwriteAnExistingFileWhenExplicitlyEnabled(): void
    {
        $source = $this->createSource();
        $destination = $this->testDirectory . '/output.pdf';
        file_put_contents($destination, 'existing content');
        $converter = new LibreOfficeConverter(
            overwrite: true,
            processRunner: $this->successfulRunner('%PDF-1.7 replacement'),
            temporaryDirectory: $this->temporaryDirectory,
        );

        $converter->convert($source, $destination);

        self::assertSame('%PDF-1.7 replacement', file_get_contents($destination));
    }

    public function testItReportsLibreOfficeFailuresAndCleansTemporaryFiles(): void
    {
        $source = $this->createSource();
        $runner = new FakeProcessRunner(
            static fn(): ProcessResult => new ProcessResult(1, '', 'invalid document'),
        );
        $converter = new LibreOfficeConverter(
            processRunner: $runner,
            temporaryDirectory: $this->temporaryDirectory,
        );

        try {
            $converter->convert($source, $this->testDirectory . '/output.pdf');
            self::fail('A failed process must throw an exception.');
        } catch (ConversionException $exception) {
            self::assertStringContainsString('exit code 1', $exception->getMessage());
            self::assertStringContainsString('invalid document', $exception->getMessage());
        }

        $this->assertTemporaryDirectoryIsEmpty();
    }

    public function testItWrapsProcessStartupAndTimeoutErrors(): void
    {
        $source = $this->createSource();
        $runner = new FakeProcessRunner(
            static fn(): never => throw new RuntimeException('process timed out'),
        );
        $converter = new LibreOfficeConverter(
            binary: 'missing-libreoffice',
            processRunner: $runner,
            temporaryDirectory: $this->temporaryDirectory,
        );

        try {
            $converter->convert($source, $this->testDirectory . '/output.pdf');
            self::fail('A process startup failure must throw an exception.');
        } catch (ConversionException $exception) {
            self::assertStringContainsString('missing-libreoffice', $exception->getMessage());
            self::assertStringContainsString('process timed out', $exception->getMessage());
            self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }

        $this->assertTemporaryDirectoryIsEmpty();
    }

    public function testItRejectsAFileWithoutAPdfHeader(): void
    {
        $source = $this->createSource();
        $converter = new LibreOfficeConverter(
            processRunner: $this->successfulRunner('not a PDF'),
            temporaryDirectory: $this->temporaryDirectory,
        );

        try {
            $converter->convert($source, $this->testDirectory . '/output.pdf');
            self::fail('An invalid PDF must throw an exception.');
        } catch (ConversionException $exception) {
            self::assertStringContainsString('valid PDF header', $exception->getMessage());
        }

        self::assertFileDoesNotExist($this->testDirectory . '/output.pdf');
        $this->assertTemporaryDirectoryIsEmpty();
    }

    public function testItDetectsWhenLibreOfficeDoesNotGenerateAnOutputFile(): void
    {
        $source = $this->createSource();
        $runner = new FakeProcessRunner(
            static fn(): ProcessResult => new ProcessResult(0, 'success', ''),
        );
        $converter = new LibreOfficeConverter(
            processRunner: $runner,
            temporaryDirectory: $this->temporaryDirectory,
        );

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('did not generate the expected PDF');

        $converter->convert($source, $this->testDirectory . '/output.pdf');
    }

    public function testItValidatesConstructorOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('timeout');

        new LibreOfficeConverter(timeout: 0);
    }

    private function createSource(string $filename = 'document.docx'): string
    {
        $source = $this->testDirectory . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($source, 'test DOCX contents');

        return $source;
    }

    private function successfulRunner(string $contents): FakeProcessRunner
    {
        return new FakeProcessRunner(
            static function (array $command) use ($contents): ProcessResult {
                $outputOption = array_search('--outdir', $command, true);

                if (false === $outputOption || !isset($command[$outputOption + 1])) {
                    throw new RuntimeException('The output directory argument was not provided.');
                }

                $source = $command[array_key_last($command)];
                $generated = $command[$outputOption + 1]
                    . DIRECTORY_SEPARATOR
                    . pathinfo($source, PATHINFO_FILENAME)
                    . '.pdf';
                file_put_contents($generated, $contents);

                return new ProcessResult(0, 'conversion completed', '');
            },
        );
    }

    private function converterThatMustNotRun(): LibreOfficeConverter
    {
        return new LibreOfficeConverter(
            processRunner: new FakeProcessRunner(
                static fn(): never => throw new RuntimeException('The process should not have run.'),
            ),
            temporaryDirectory: $this->temporaryDirectory,
        );
    }

    private function assertTemporaryDirectoryIsEmpty(): void
    {
        $items = scandir($this->temporaryDirectory);
        self::assertNotFalse($items);
        self::assertSame([], array_values(array_diff($items, ['.', '..'])));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                continue;
            }

            if ($item->isDir() && !$item->isLink()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}
