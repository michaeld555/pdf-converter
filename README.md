# PDF Converter

PHP library to convert DOCX files to PDF using a local LibreOffice installation.
Conversions run headless with isolated profiles and temporary directories, enabling
concurrent executions with minimal conflict.

## Requirements

### PHP

- PHP 8.2 or higher.
- Composer 2.
- `proc_open` enabled (used by `symfony/process`).

### LibreOffice

The `libreoffice` or `soffice` binary must be installed on the system:

```bash
# Ubuntu / Debian
sudo apt-get install --yes libreoffice-writer

# Alpine
apk add --no-cache libreoffice

# macOS
brew install --cask libreoffice
```

On Windows, install LibreOffice and configure the full path to `soffice.exe`.

## Installation

```bash
composer require michaeld555/pdf-converter
```

## Quick start

```php
use Michaeld555\PdfConverter\Converter;

$converter = new Converter();
$converter->convert('/data/input.docx', '/data/output.pdf');
```

Existing files are not overwritten by default.

## Configuration

```php
use Michaeld555\PdfConverter\Converter;
use Michaeld555\PdfConverter\Driver\LibreOfficeConverter;

$driver = new LibreOfficeConverter(
    binary: '/usr/bin/libreoffice',
    timeout: 120.0,
    overwrite: false,
    temporaryDirectory: '/var/tmp/pdf-converter',
);

$converter = new Converter($driver);
$converter->convert('/data/input.docx', '/data/output.pdf');
```

### Driver options

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `binary` | `string` | `libreoffice` | Binary name or path. |
| `timeout` | `float` | `60.0` | Max conversion time in seconds. |
| `overwrite` | `bool` | `false` | Allow overwriting an existing PDF. |
| `processRunner` | `?ProcessRunner` | Symfony Runner | Advanced integration or testing. |
| `temporaryDirectory` | `?string` | System temp | Base directory for isolated files. |

## Error handling

All failures throw `Michaeld555\PdfConverter\Exception\ConversionException`:

```php
use Michaeld555\PdfConverter\Converter;
use Michaeld555\PdfConverter\Exception\ConversionException;

try {
    (new Converter())->convert('/data/input.docx', '/data/output.pdf');
} catch (ConversionException $exception) {
    error_log($exception->getMessage());
}
```

Checked scenarios include missing/invalid source or destination, timeout, missing
LibreOffice, process errors, and invalid PDF output.

## API reference

### `Michaeld555\PdfConverter\Converter`

Main facade. Uses `LibreOfficeConverter` when no driver is provided.

```php
public function __construct(?DocumentConverter $driver = null)
public function convert(string $source, string $destination): void
```

### `Michaeld555\PdfConverter\Driver\LibreOfficeConverter`

```php
public function __construct(
    string $binary = 'libreoffice',
    float $timeout = 60.0,
    bool $overwrite = false,
    ?ProcessRunner $processRunner = null,
    ?string $temporaryDirectory = null,
)
public function convert(string $source, string $destination): void
```

### Path rules

- Source must be a local, readable `.docx` file (no URLs).
- Destination must end in `.pdf`; its parent directory must exist and be writable.
- Directories are not created automatically.

## Custom drivers

Implement `DocumentConverter` to integrate other mechanisms (Microsoft Graph,
internal queue, etc.):

```php
use Michaeld555\PdfConverter\Contract\DocumentConverter;
use Michaeld555\PdfConverter\Converter;

final class CompanyConverter implements DocumentConverter
{
    public function convert(string $source, string $destination): void {}
}

$converter = new Converter(new CompanyConverter());
```

## Docker

```dockerfile
FROM php:8.4-cli
RUN apt-get update && apt-get install --yes --no-install-recommends \
        git libreoffice-writer unzip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
COPY . .
CMD ["php", "app.php"]
```

## Testing & quality

```bash
composer check              # All checks
composer test               # Unit + integration
composer analyse            # PHPStan max level
composer cs:check           # Code style
composer security:audit     # Dependency audit
```

Integration tests require LibreOffice, `pdfinfo` (poppler-utils), and the ZIP
extension. Use `LIBREOFFICE_BINARY` env var to set a custom binary for tests.

## Troubleshooting

- **LibreOffice not found**: Verify `proc_open` is enabled and set the `binary` path.
- **Timeout**: Increase `timeout` for large documents or slow storage.
- **Wrong fonts**: Install the document fonts and run `fc-cache -f`.
- **Permission denied**: Check read/write access for origin, destination, and temp
  directories under the PHP process user.
- **File exists**: Enable `overwrite: true` or choose another destination.

## Security

- No documents are sent to third parties.
- No URL downloading; all input must be local.
- Process arguments use a list (no shell command injection).
- Output is validated to start with `%PDF-` before saving.
- Temporary files use random names with restricted permissions.

## Migration from 1.x

| Before | After |
| --- | --- |
| `Converter::docx_to_pdf()` static method | `(new Converter())->convert()` instance method |
| PHP 8.0 minimum | PHP 8.2 minimum |
| Remote URLs accepted | Local files only |
| Destination could be a directory | Full path with `.pdf` required |
| Overwrite by default | Explicit `overwrite: true` required |
| FPDI, TCPDF, Office endpoint | Removed |

The legacy `Michaeld555\Converter::docx_to_pdf()` adapter is available for gradual
migration but is deprecated.

## Project structure

```text
src/
├── Contract/   Public interfaces
├── Driver/     Conversion implementations
├── Exception/  Library exceptions
├── Legacy/     Temporary 1.x compatibility
├── Process/    Isolated process execution
└── Converter.php

tests/
├── Integration/
├── Support/
└── Unit/
```

## License

MIT. See [LICENSE](LICENSE).
