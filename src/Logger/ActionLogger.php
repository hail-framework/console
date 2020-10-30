<?php

namespace Hail\Console\Logger;

class ActionLogger
{
    public $fd;

    public function __construct(resource $fd = null)
    {
        $this->fd = $fd ?: \fopen('php://stderr', 'wb');
    }

    public function __destruct()
    {
        if ($this->fd) {
            \fclose($this->fd);
        }
    }

    public function newAction(string $title, string $desc = '', string $status = 'waiting')
    {
        return new LogAction($this->fd, $title, $desc, $status);
    }
}
