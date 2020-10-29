<?php

namespace Hail\Console\Component;

use Hail\Console\Formatter;

class ProgressBar
{
    protected $terminalWidth = 78;

    protected $formatter;

    protected $stream;

    protected $leftDecorator = '[';

    protected $rightDecorator = ']';

    protected $columnDecorator = ' | ';

    protected $barCharacter = '#';

    protected $descFormat = '%finished%/%total% %unit% | %percentage% | %eta_period%';

    protected $unit;

    protected $title;

    protected $start;

    protected $etaTime = '--:--';

    protected $etaPeriod = '--';

    public function __construct($stream)
    {
        $this->stream = $stream;
        $this->formatter = Formatter::getInstance();

        if (\getenv('COLUMNS')) {
            $this->terminalWidth = (int) \getenv('COLUMNS');
        } else {
            $paths = \explode(':', \getenv('PATH'));
            foreach ($paths as $path) {
                $bin = $path . DIRECTORY_SEPARATOR . 'tput';
                if (\file_exists($bin) && \is_executable($bin)) {
                    $this->terminalWidth = (int) \exec('tput cols');
                    break;
                }
            }
        }
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }

    public function start(string $title = null): void
    {
        if ($title) {
            $this->setTitle($title);
        }

        $this->start = \microtime(true);
    }

    public function update(int $finished, int $total): void
    {
        $percentage = $total > 0 ? \round($finished / $total, 2) : 0.0;
        $trigger = $finished % 3;

        if ($trigger) {
            $now = \microtime(true);
            if ($remain = $this->calculateRemainingSeconds($finished, $total, $now)) {
                $this->etaTime = \date('H:i', $now + $remain);
                $this->etaPeriod = $this->calculateEstimatedPeriod($remain);
            } else {
                $this->etaTime = '--:--';
                $this->etaPeriod = '--';
            }
        }

        $desc = \strtr($this->descFormat, [
            '%finished%' => $finished,
            '%total%' => $total,
            '%unit%' => $this->unit,
            '%percentage%' => ($percentage * 100) . '%',
            '%eta_time%' => 'ETA: ' . $this->etaTime,
            '%eta_period%' => 'ETA: ' . $this->etaPeriod,
        ]);

        $barSize = $this->terminalWidth
            - \mb_strlen($desc)
            - \mb_strlen($this->leftDecorator)
            - \mb_strlen($this->rightDecorator)
            - \mb_strlen($this->columnDecorator);

        if ($this->title) {
            $barSize -= (\mb_strlen($this->title) + \mb_strlen($this->columnDecorator));
        }

        $sharps = \ceil($barSize * $percentage);

        \fwrite($this->stream, "\r"
            . ($this->title ? $this->title . $this->columnDecorator : '')
            . $this->formatter->decorate($this->leftDecorator, ['fg' => $trigger ? 'purple' : 'light_purple'])
            . $this->formatter->decorate(\str_repeat($this->barCharacter, $sharps), ['fg' => $trigger ? 'purple' : 'light_purple'])
            . \str_repeat(' ', \max($barSize - $sharps, 0))
            . $this->formatter->decorate($this->rightDecorator, ['fg' => $trigger ? 'purple' : 'light_purple'])
            . $this->columnDecorator
            . $this->formatter->decorate($desc, ['fg' => $trigger ? 'light_gray' : 'white'])
        );

        // hide cursor
        // fputs($this->stream, "\033[?25l");

        // show cursor
        // fputs($this->stream, "\033[?25h");
    }

    public function finish(string $title = null): void
    {
        if ($title) {
            $this->setTitle($title);
        }

        \fwrite($this->stream, PHP_EOL);
    }

    private function calculateRemainingSeconds(int $proceeded, int $total, float $now): ?float
    {
        $secondDiff = ($now - $this->start);
        $speed = $secondDiff > 0 ? $proceeded / $secondDiff : 0;
        $remaining = $total - $proceeded;
        if ($speed > 0) {
            return $remaining / $speed;
        }

        return null;
    }

    private function calculateEstimatedPeriod(float $remainingSeconds): string
    {
        $str = '';

        $days = $hours = $minutes = 0;
        if ($remainingSeconds > 86400) {
            $days = \ceil($remainingSeconds / 86400);
            $remainingSeconds %= 86400;
        }

        if ($remainingSeconds > 3600) {
            $hours = \ceil($remainingSeconds / 3600);
            $remainingSeconds %= 3600;
        }

        if ($remainingSeconds > 60) {
            $minutes = \ceil($remainingSeconds / 60);
            $remainingSeconds %= 60;
        }

        if ($days > 0) {
            $str .= $days . 'd';
        }

        if ($hours) {
            $str .= $hours . 'h';
        }

        if ($minutes) {
            $str .= $minutes . 'm';
        }

        if ($remainingSeconds > 0) {
            $str .= ((int) $remainingSeconds) . 's';
        }

        return $str;
    }
}
