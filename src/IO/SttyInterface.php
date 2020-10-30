<?php

namespace Hail\Console\IO;

/**
 * The interface of classes which handle stty.
 */
interface SttyInterface
{
    /**
     * Turn on echo.
     */
    public function enableEcho(): void;

    /**
     * Turn off echo.
     */
    public function disableEcho(): void;

    /**
     * Dump all current settings in a-stty readable form.
     *
     * @return string
     */
    public function dump(): string;

    /**
     * Turn off echoing and execute the callback function.
     *
     * @param \Closure $callback
     *
     * @return mixed
     */
    public function withoutEcho(\Closure $callback);
}
