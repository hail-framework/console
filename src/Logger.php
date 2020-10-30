<?php
/*
 * This file is part of the CLIFramework package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Hail\Console;

use Hail\Singleton\SingletonTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

class Logger implements LoggerInterface
{
    use LoggerTrait;
    use SingletonTrait;

    protected $logLevels = [
        LogLevel::EMERGENCY => 8,
        LogLevel::ALERT => 7,
        LogLevel::CRITICAL => 6,
        LogLevel::ERROR => 5,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 3,
        LogLevel::INFO => 2,
        LogLevel::DEBUG => 1,
    ];

    public $levelStyles = [
        LogLevel::EMERGENCY => 'strong_red',
        LogLevel::ALERT => 'strong_red',
        LogLevel::CRITICAL => 'strong_red',
        LogLevel::ERROR => 'red',
        LogLevel::WARNING => 'yellow',
        LogLevel::NOTICE => 'green',
        LogLevel::INFO => 'green',
        LogLevel::DEBUG => 'white',
    ];

    /**
     * current level
     *
     * any message level greater than or equal to this will be displayed.
     * */
    public $level = 3;

    protected $indent = 0;

    protected $indentCharacter = '  ';

    /**
     * foramtter class
     *
     * @var Formatter
     */
    public $formatter;

    /**
     * @var resource|null
     */
    private $stream;

    protected function init(): void
    {
        $this->formatter = Formatter::getInstance();
        $this->stream = \fopen('php://output', 'wb');
    }

    public function __destruct()
    {
        \fclose($this->stream);
    }

    public function setLevel(?string $level): self
    {
        if ($level === null) {
            $this->level = 9;
        }

        if (isset($this->logLevels[$level])) {
            $this->level = $this->logLevels[$level];
        }

        return $this;
    }

    public function getLevel(string $level = null)
    {
        if ($level === null) {
            return $this->level;
        }

        return $this->logLevels[$level] ?? null;
    }

    public function setStream(resource $stream): self
    {
        if ($this->stream !== null) {
            \fclose($this->stream);
        }

        $this->stream = $stream;

        return $this;
    }

    public function indent(int $level = 1): self
    {
        $this->indent += $level;

        return $this;
    }

    public function unIndent(int $level = 1): self
    {
        $this->indent = \max(0, $this->indent - $level);

        return $this;
    }

    public function resetIndent(): self
    {
        $this->indent = 0;

        return $this;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed        $level
     * @param string|array $message
     * @param array        $context
     *
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        if (!isset($this->logLevels[$level])) {
            $level = LogLevel::DEBUG;
        }

        if ($this->logLevels[$level] < $this->level) {
            // do not print.
            return;
        }

        if ($this->level === 3 && $this->logLevels[$level] === 3) {
            $style = 'dim';
        } else {
            $style = $this->levelStyles[$level];
        }

        if ($this->indent) {
            $this->write(\str_repeat($this->indentCharacter, $this->indent));
        }

        if (\is_array($message)) {
            $this->writeln(\print_r($message, true), $style);
        } else {
            $this->writeln($message, $style);
        }
    }

    /**
     * @param string      $text text to write by `writer`
     * @param string|null $style
     *
     * @return Logger
     */
    public function write(string $text, string $style = null): self
    {
        if ($style !== null) {
            $text = $this->formatter->format($text, $style);
        } else {
            $text = $this->format($text);
        }

        \fwrite($this->stream, $text);

        return $this;
    }

    /**
     * @param string      $text write text and append a newline character.
     * @param string|null $style
     *
     * @return Logger
     */
    public function writeln(string $text, string $style = null): self
    {
        return $this->write($text, $style)->newline();
    }

    /**
     * Append a newline charactor to the console
     */
    public function newline(): self
    {
        \fwrite($this->stream, "\n");

        return $this;
    }

    /**
     * Write exception to console.
     */
    public function logException(\Exception $exception): self
    {
        echo $exception->getMessage();
        $this->newline();

        return $this;
    }

    public function format(string $text): string
    {
        return \preg_replace_callback('#<(\w+)>(.*?)</\1>#', [$this, 'formatCallback'], $text);
    }

    private function formatCallback(array $matches): string
    {
        [$raw, $style, $text] = $matches;

        if ($style === 'b') {
            $style = 'bold';
        } elseif ($style === 'u') {
            $style = 'underline';
        }

        if ($this->formatter->hasStyle($style)) {
            return $this->formatter->format($text, $style);
        }

        return $raw;
    }
}
