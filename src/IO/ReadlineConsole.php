<?php

namespace Hail\Console\IO;

\defined('READLINE_EXTENSION') || \define('READLINE_EXTENSION', \extension_loaded('readline'));

/**
 * Console utilities using readline.
 */
class ReadlineConsole extends Console
{
    /**
     * @var SttyInterface
     */
    private $stty;

    public function __construct(SttyInterface $stty)
    {
        $this->stty = $stty;
    }

    public static function isAvailable(): bool
    {
        return READLINE_EXTENSION;
    }

    public function readLine(string $prompt): string
    {
        $line = \readline($prompt);
        \readline_add_history($line);

        return $line;
    }

    public function readPassword(string $prompt): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            echo $prompt;

            return $this->readPasswordForWin();
        }

        return $this->noEcho(static function () use ($prompt) {
            return \readline($prompt);
        });
    }

    public function noEcho(\Closure $callback)
    {
        return $this->stty->withoutEcho($callback);
    }
}
