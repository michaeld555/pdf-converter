<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter\Tests\Unit;

use Michaeld555\PdfConverter\Contract\DocumentConverter;
use Michaeld555\PdfConverter\Converter;
use PHPUnit\Framework\TestCase;

final class ConverterTest extends TestCase
{
    public function testItDelegatesConversionToTheConfiguredDriver(): void
    {
        $driver = new class implements DocumentConverter {
            /**
             * @var list<array{source: string, destination: string}>
             */
            public array $calls = [];

            public function convert(string $source, string $destination): void
            {
                $this->calls[] = compact('source', 'destination');
            }
        };

        $converter = new Converter($driver);
        $converter->convert('/documents/input.docx', '/documents/output.pdf');

        self::assertSame([
            [
                'source' => '/documents/input.docx',
                'destination' => '/documents/output.pdf',
            ],
        ], $driver->calls);
    }
}
