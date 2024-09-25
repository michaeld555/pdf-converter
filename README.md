## Pdf Converter

## Installation

Require this package in your composer.json and update composer. This will download the package.

    composer require michaeld555/pdf-converter
  
## Usage

You can create a new Converter instance and pass the docx file url and pdf file path. This will convert and save the pdf file in the path passed.

```php
    use PdfConverter\Converter;

    Converter::docx_to_pdf('https://example.com/file.docx', 'path_to_file/');

    
    if (file_exists('path_to_file/file.pdf')) {

        echo "the file was converted successfully";

    } else {

        echo "an error occurred during the file conversion";

    }

```