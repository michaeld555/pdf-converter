<?php

declare(strict_types=1);

namespace Michaeld555\PdfConverter\Tests\Unit;

use Michaeld555\Converter;
use Michaeld555\PdfConverter\Exception\ConversionException;
use PHPUnit\Framework\TestCase;

final class LegacyConverterTest extends TestCase
{
    public function testItRejectsTheFormerRemoteUrlWorkflowWithAnActionableError(): void
    {
        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Only local DOCX files are supported');

        @Converter::docx_to_pdf('https://example.com/document.docx', '/tmp/document.pdf');
    }

    public function testItRejectsNullArgumentsWithAnActionableError(): void
    {
        $this->expectException(ConversionException::class);

        @Converter::docx_to_pdf(null, null);
    }
}
