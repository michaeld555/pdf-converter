<?php

namespace Michaeld555\PdfConverter\Providers;

class ConverterProvider {
    
    public static function isWin(): bool
    {
        return stripos(PHP_OS, 'WIN') === 0;
    }

    public static function pythonPath()
    {

        $path = dirname(__FILE__);

        $path = str_replace("Providers", "Python", $path);

        $path = $path.'/python.exe';

        return $path;

    }

    public static function converter_script_path()
    {

        $scriptPath = dirname(__FILE__);

        $scriptPath = str_replace("Providers", "Scripts", $scriptPath);

        $scriptPath = $scriptPath.'/converter.py';

        return $scriptPath;

    }

    public static function converter_command($file, $output)
    {

        $isWin = self::isWin();

        $script = self::converter_script_path();

        $command = ($isWin) ? self::win_command($script, $file, $output) : self::linux_command($file, $output);

        return $command;

    }

    public static function win_command($parser, $file, $output)
    {

        $pyPath = self::pythonPath();

        $command = escapeshellcmd("$pyPath $parser $file $output");

        return $command;

    }

    public static function linux_command($file, $output)
    {

        $command = escapeshellcmd("libreoffice --headless --convert-to pdf --outdir $output $file");

        return $command;

    }

    public static function execute_command($command)
    {

        $output = shell_exec($command);

        return $output;

    }


}
