<?php

namespace Michaeld555\PdfConverter;

use Michaeld555\PdfConverter\Providers\ConverterProvider;

class Converter extends ConverterProvider {

    /**
     * Convert the input file to pdf and save in the output path
     *
     * @param string $file Path of the input file
     * @param string $output Path of the output file
     */
    public static function parse(?string $file, ?string $output)
    {

        $command = self::converter_command($file, $output);

        $result = self::execute_command($command);

        //print($result); // for log test only

    }


}