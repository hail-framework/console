<?php

namespace Hail\Console\IO;

class NullStty implements SttyInterface
{
    public function enableEcho(): void
    {
    }

    public function disableEcho(): void
    {
    }

    public function dump(): string
    {
        return '';
    }

    public function withoutEcho(\Closure $callback)
    {
        return $callback();
    }
}
