<?php

namespace Hail\Console\IO;

class UnixStty implements SttyInterface
{
    public function enableEcho(): void
    {
        shell_exec('stty echo');
    }

    public function disableEcho(): void
    {
        shell_exec('stty -echo');
    }

    public function dump(): string
    {
        return shell_exec('stty -g');
    }

    public function withoutEcho(\Closure $callback)
    {
        $oldStyle = $this->dump();
        // don't display characters from user input.
        $this->disableEcho();
        $result = null;

        try {
            $result = $callback();
            $this->restoreStyle($oldStyle);
        } catch (\Exception $e) {
            $this->restoreStyle($oldStyle);
            throw $e;
        }

        return $result;
    }

    private function restoreStyle(string $style): void
    {
        shell_exec('stty ' . $style);
    }
}
