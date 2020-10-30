<?php

namespace Hail\Console\IO;

\defined('IS_WINDOWS') || \define('IS_WINDOWS', PHP_OS_FAMILY === 'Windows');

class Stty
{
    /**
     * Turn on echo.
     */
    public static function enableEcho(): void
    {
        if (IS_WINDOWS) {
            return;
        }

        shell_exec('stty echo');
    }

    /**
     * Turn off echo.
     */
    public static function disableEcho(): void
    {
        if (IS_WINDOWS) {
            return;
        }

        shell_exec('stty -echo');
    }

    /**
     * Dump all current settings in a-stty readable form.
     *
     * @return string
     */
    public static function dump(): string
    {
        if (IS_WINDOWS) {
            return '';
        }

        return shell_exec('stty -g');
    }

    /**
     * Turn off echoing and execute the callback function.
     *
     * @param \Closure $callback
     *
     * @return mixed
     * @throws \Exception
     */
    public static function withoutEcho(\Closure $callback)
    {
        if (IS_WINDOWS) {
            return $callback();
        }

        $oldStyle = self::dump();
        // don't display characters from user input.
        self::disableEcho();
        $result = null;

        try {
            $result = $callback();
            self::restoreStyle($oldStyle);
        } catch (\Exception $e) {
            self::restoreStyle($oldStyle);
            throw $e;
        }

        return $result;
    }

    private static function restoreStyle(string $style): void
    {
        shell_exec('stty ' . $style);
    }
}
