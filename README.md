## Docx TO PDF Converter for PHP

## Installation

### PHP Projects
Require this package in your composer.json and update composer. This will download the package.

    composer require michaeld555/pdf-converter
  
## Using

You can create a new Converter instance and pass the docx input path and pdf output file path. This will convert and save the pdf output file in the path passed.

```php
    use Michaeld555\SecureShell\Converter;

    Converter::parse('path_to_file/file.docx', 'path_to_file/');

    
    if (file_exists('path_to_file/file.pdf')) {

        echo "the file was converted successfully";

    } else {

        echo "an error occurred during the conversation";

    }

```