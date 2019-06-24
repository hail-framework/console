<?php
namespace Hail\Console\ConsoleInfo;

class TputConsoleInfo implements ConsoleInfoInterface
{
    public function getColumns()
    {
        return (int) exec('tput cols');
    }

    public function getRows()
    {
        return (int) exec('tput lines');
    }

    public static function hasSupport()
    {
        $paths = explode(':', getenv('PATH'));
        foreach ($paths as $path) {
            $bin = $path . DIRECTORY_SEPARATOR . 'tput';
            if (file_exists($bin) && is_executable($bin)) {
                return true;
            }
        }
        return false;
    }
}
