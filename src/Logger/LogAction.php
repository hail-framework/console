<?php

namespace Hail\Console\Logger;

use Hail\Console\Formatter;

class LogAction
{
    public $title;

    public $desc;

    public $status;

    protected $fd;

    protected $formatter;

    protected $actionColumnWidth = 38;

    public function __construct(resource $fd, string $title, string $desc, string $status = 'waiting')
    {
        $this->fd = $fd;
        $this->formatter = Formatter::getInstance();
        $this->title = $title;
        $this->desc = $desc;
        $this->status = $status;

        \fwrite($this->fd, "\e[?25l"); //hide
    }

    public function setStatus(string $status, string $style = 'green'): self
    {
        $this->status = $status;
        $this->update($style);

        return $this;
    }

    public function setActionColumnWidth(int $width): self
    {
        $this->actionColumnWidth = $width;

        return $this;
    }

    protected function update(string $style = 'green'): self
    {
        $padding = \max($this->actionColumnWidth - \strlen($this->title), 1);
        $buf = \sprintf('  %s % -20s',
            $this->formatter->format($this->title, $style) . \str_repeat(' ', $padding),
            $this->status
        );
        \fwrite($this->fd, "$buf\r");
        \fflush($this->fd);

        return $this;
    }

    protected function finalize(): void
    {
        \fwrite($this->fd, "\n");
        \fflush($this->fd);
        \fwrite($this->fd, "\e[?25h"); // show

    }

    public function done(): void
    {
        $this->setStatus('done');
        $this->finalize();
    }
}
