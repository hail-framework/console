<?php

namespace Hail\Console\Component;

\defined('READLINE_EXTENSION') || \define('READLINE_EXTENSION', \extension_loaded('readline'));
\defined('IS_WINDOWS') || \define('IS_WINDOWS', PHP_OS_FAMILY === 'Windows');

/**
 * Console utilities
 */
class Console
{
    /**
     * Read a line from user input.
     */
    public static function readLine(string $prompt): string
    {
        if (READLINE_EXTENSION) {
            $line = \readline($prompt);
            \readline_add_history($line);

            return $line;
        }

        echo $prompt;

        return self::read();
    }

    /**
     * Read a line from user input without echoing if possible.
     */
    public static function readPassword(string $prompt): string
    {
        if (IS_WINDOWS) {
            echo $prompt;

            return self::readPasswordForWin();
        }

        if (READLINE_EXTENSION) {
            return self::withoutEcho(static function () use ($prompt) {
                return \readline($prompt);
            });
        }

        echo $prompt;

        return self::withoutEcho(
            \Closure::fromCallable('self::read')
        );
    }

    public static function completion(\Closure $callback): void
    {
        if (READLINE_EXTENSION) {
            \readline_completion_function($callback);
        }
    }

    protected static function read(): string
    {
        return \rtrim(\fgets(STDIN), "\n");
    }

    /**
     * Read a line from user input without echoing if possible.
     *
     * @return string
     */
    protected static function readPasswordForWin(): string
    {
        $exe = __DIR__ . '\bin\hiddeninput.exe';

        $return = \rtrim(\shell_exec($exe));

        echo "\n";

        return $return;
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

        $style = \shell_exec('stty -g');
        // don't display characters from user input.
        \shell_exec('stty -echo');
        $result = null;

        try {
            return $callback();
        } finally {
            \shell_exec('stty ' . $style);
        }
    }
}
