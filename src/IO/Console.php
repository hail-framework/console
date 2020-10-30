<?php

namespace Hail\Console\IO;

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
            return Stty::withoutEcho(static function () use ($prompt) {
                return \readline($prompt);
            });
        }

        echo $prompt;

        return Stty::withoutEcho(
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
}
