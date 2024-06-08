<?php

namespace Michaeld555\SecureShell;

class Converter {

    /**
     * Convert the input file to pdf and save in the output path
     *
     * @param string $file Path of the input file
     * @param string $encoding Path of the output file
     */
    public static function parse(?string $file, ?string $output)
    {

        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $py = __DIR__.'/python/win/python.exe';

        $converter = __DIR__.'/converter.py';

        $file = $file;

        $output = $output;

        if ($isWin) {

            $command = escapeshellcmd("$py $converter $file $output");

        } else {

            $command = escapeshellcmd("libreoffice --headless --convert-to pdf --outdir $output $file");

        }

        $output = shell_exec($command);

        print($output);

    }


}