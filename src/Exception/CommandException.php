<?php
namespace Hail\Console\Exception;

use Hail\Console\CommandInterface;

class CommandException extends \RuntimeException
{
    public $command;

    public function __construct(CommandInterface $command, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        $this->command = $command;
        parent::__construct($message, $code, $previous);
    }

    public function getCommand()
    {
        return $this->command;
    }
}
