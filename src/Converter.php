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

        $py = __DIR__.'/converter.py';

        $file = $file;

        $output = $output;

        $command = escapeshellcmd("python $py $file $output");

        $output = shell_exec($command);

    }


}