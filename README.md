## Docx to Pdf Converter

## Installation

Require this package in your composer.json and update composer. This will download the package.

    composer require michaeld555/pdf-converter
  
## Usage

You can create a new Converter instance and pass the docx file path and pdf file path. This will convert and save the pdf file in the path passed.

```php
    use Michaeld555\PdfConverter\Converter;

    Converter::parse('path_to_file/file.docx', 'path_to_file/');

    
    if (file_exists('path_to_file/file.pdf')) {

        echo "the file was converted successfully";

    } else {

        echo "an error occurred during the file conversion";

    }

```

## Notes:

- If you are using a windows system, make sure you have the Microsoft Word application installed on your system

- If you are using a linux system, make sure you have the [libreoffice](https://www.libreoffice.org/download/) package installed on your system, and that your application is enabled to execute customs scripts