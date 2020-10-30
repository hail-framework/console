<?php

namespace Hail\Console\Exception;

/**
 * Class CommandClassNotFoundException
 *
 * @package Hail\Console\Exception
 */
class CommandClassNotFoundException extends \RuntimeException
{
    /**
     * CommandClassNotFoundException constructor.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        parent::__construct("Command $class not found.");
    }
}
