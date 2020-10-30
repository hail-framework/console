<?php

namespace Hail\Console\IO;


abstract class Console
{
    /**
     * Read a line from user input.
     *
     * @return string
     */
    abstract public function readLine(string $prompt): string;

    /**
     * Read a line from user input without echoing if possible.
     *
     * @return string
     */
    abstract public function readPassword(string $prompt): string;

    /**
     * Turn off echo and execute the callback function.
     *
     * @param \Closure $callback the callback function to execute.
     *
     * @return mixed return the result value returned by the callback.
     */
    abstract public function noEcho(\Closure $callback);

    /**
     * Read a line from user input without echoing if possible.
     *
     * @return string
     */
    protected function readPasswordForWin(): string
    {
        $exe = __DIR__ . '\bin\hiddeninput.exe';

        $return = rtrim(shell_exec($exe));

        echo "\n";

        return $return;
    }
}
