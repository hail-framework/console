<?php

namespace Hail\Console\IO;

/**
 * Console utilities using STDIN.
 */
class StandardConsole extends Console
{
    /**
     * @var SttyInterface
     */
    private $stty;

    public function __construct(SttyInterface $stty)
    {
        $this->stty = $stty;
    }

    public function readLine(string $prompt): string
    {
        echo $prompt;

        return $this->read();
    }

    public function readPassword(string $prompt): string
    {
        echo $prompt;

        if (PHP_OS_FAMILY === 'Windows') {
            return $this->readPasswordForWin();
        }

        return $this->noEcho(
            \Closure::fromCallable([$this, 'read'])
        );
    }

    public function noEcho(\Closure $callback)
    {
        return $this->stty->withoutEcho($callback);
    }

    private function read()
    {
        return \rtrim(\fgets(STDIN), "\n");
    }
}
