## PDF Converter for PHP

## Installation

### PHP Projects
Require this package in your composer.json and update composer. This will download the package.

    composer require michaeld555/pdf-converter
  
## Using

You can create a new Converter instance and pass the input and output file paths. This will save the output file in the path passed.

```php
    use Michaeld555\SecureShell\Converter;

    Converter::parse('path_to_file/file.docx', 'path_to_file/file.pdf');
```