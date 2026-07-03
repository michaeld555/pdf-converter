<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter\Tests\Integration;

use Michaeld555\PdfConverter\Driver\LibreOfficeConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use ZipArchive;

final class LibreOfficeConverterTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        $this->testDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'pdf-converter-integration-'
            . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->testDirectory, 0o700));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->testDirectory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->testDirectory);
    }

    public function testItConvertsPortraitAndLandscapePagesUsingLibreOffice(): void
    {
        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('The ZIP extension is required to build the DOCX fixture.');
        }

        $binary = getenv('LIBREOFFICE_BINARY') ?: (new ExecutableFinder())->find('libreoffice');
        $binary = $binary ?: (new ExecutableFinder())->find('soffice');

        if (!$binary) {
            self::markTestSkipped('LibreOffice was not found.');
        }

        $pdfInfo = (new ExecutableFinder())->find('pdfinfo');

        if (!$pdfInfo) {
            self::markTestSkipped('pdfinfo was not found.');
        }

        $source = $this->testDirectory . DIRECTORY_SEPARATOR . 'integration.docx';
        $destination = $this->testDirectory . DIRECTORY_SEPARATOR . 'integration.pdf';
        $this->createMinimalDocx($source);

        (new LibreOfficeConverter(binary: $binary, timeout: 90))->convert($source, $destination);

        self::assertFileExists($destination);
        self::assertGreaterThan(100, filesize($destination));
        self::assertSame('%PDF-', file_get_contents($destination, false, null, 0, 5));

        $inspection = new Process([$pdfInfo, '-f', '1', '-l', '2', $destination]);
        self::assertSame(0, $inspection->run(), $inspection->getErrorOutput());
        self::assertMatchesRegularExpression('/Pages:\\s+2/', $inspection->getOutput());
        self::assertSame(
            2,
            preg_match_all(
                '/Page\\s+(\\d+) size:\\s+([\\d.]+) x ([\\d.]+)/',
                $inspection->getOutput(),
                $pageSizes,
                PREG_SET_ORDER,
            ),
        );
        self::assertLessThan((float) $pageSizes[0][3], (float) $pageSizes[0][2], 'Page 1 must be portrait.');
        self::assertGreaterThan((float) $pageSizes[1][3], (float) $pageSizes[1][2], 'Page 2 must be landscape.');
    }

    private function createMinimalDocx(string $path): void
    {
        $archive = new ZipArchive();
        self::assertTrue($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $archive->addFromString('[Content_Types].xml', <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
                <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
                <Default Extension="xml" ContentType="application/xml"/>
                <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
            </Types>
            XML);
        $archive->addFromString('_rels/.rels', <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
            </Relationships>
            XML);
        $archive->addFromString('word/document.xml', <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
                <w:body>
                    <w:p>
                        <w:r><w:t>Portrait page</w:t></w:r>
                    </w:p>
                    <w:p>
                        <w:pPr>
                            <w:sectPr>
                                <w:type w:val="nextPage"/>
                                <w:pgSz w:w="11906" w:h="16838"/>
                            </w:sectPr>
                        </w:pPr>
                    </w:p>
                    <w:p>
                        <w:r><w:t>Landscape page</w:t></w:r>
                    </w:p>
                    <w:sectPr>
                        <w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>
                    </w:sectPr>
                </w:body>
            </w:document>
            XML);
        self::assertTrue($archive->close());
    }
}
