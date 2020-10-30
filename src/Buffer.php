<?php

namespace Hail\Console;

class Buffer
{
    private $content;

    private $indent = 0;

    private $indentChar = '  ';

    private $indentCache = '';

    public function __construct(string $content = '')
    {
        $this->content = $content;
    }

    public function indent(): void
    {
        $this->indent++;
        $this->updateIndentCache();
    }

    /**
     * Set indent level
     *
     * @param int $indent
     */
    public function setIndent(int $indent): void
    {
        if ($this->indent !== $indent) {
            $this->indent = $indent;
            $this->updateIndentCache();
        }
    }

    /**
     * Get current indent level
     *
     * @return int
     */
    public function getIndent(): int
    {
        return $this->indent;
    }

    public function setIndentChar(string $char): void
    {
        $this->indentChar = $char;
    }

    public function getIndentChar(): string
    {
        return $this->indentChar;
    }

    public function unIndent(): void
    {
        if ($this->indent > 0) {
            $this->indent--;
            $this->updateIndentCache();
        }
    }

    private function updateIndentCache(): void
    {
        $this->indentCache = $this->makeIndent($this->indent);
    }

    private function makeIndent(int $level): string
    {
        return \str_repeat($this->indentChar, $level);
    }

    /**
     * Append a text to the buffer
     *
     * @param string $text
     */
    public function append(string $text): void
    {
        $this->content .= $text;
    }

    /**
     * Append a line with indent to the buffer
     *
     * @param string $line
     * @param int    $indent
     */
    public function appendLine(string $line, int $indent = 0): void
    {
        $this->content .= ($indent ? $this->makeIndent($indent) : $this->indentCache) . $line . "\n";
    }

    /**
     * Append multiple lines with indent to the buffer
     *
     * @param string[] $lines
     * @param int      $indent
     */
    public function appendLines(array $lines, int $indent = 0): void
    {
        foreach ($lines as $line) {
            $this->appendLine($line, $indent);
        }
    }

    /**
     * Append a string and escape with charlist
     *
     * @param string      $line
     * @param string|null $charlist
     */
    public function appendEscape(string $line, string $charlist = null): void
    {
        if ($charlist === null) {
            $this->content .= \addslashes($line);
        } else {
            $this->content .= \addcslashes($line, $charlist);
        }
    }

    /**
     * Append a string with double quotes
     *
     * @param string $str
     */
    public function appendQuote(string $str): void
    {
        $this->content .= '"' . \addcslashes($str, '"') . '"';
    }

    /**
     * Append a string with single quotes
     *
     * @param string $str
     */
    public function appendSingleQuote(string $str): void
    {
        $this->content .= "'" . \addslashes($str) . "'";
    }

    /**
     * Append a new line to the buffer
     */
    public function newLine(): void
    {
        $this->content .= "\n";
    }

    /**
     * Append a string block (multilines)
     *
     * @param string $block
     * @param int    $indent = 0
     */
    public function appendBlock(string $block, int $indent = 0): void
    {
        $lines = \explode("\n", $block);
        foreach ($lines as $line) {
            $this->appendLine($line, $indent);
        }
    }

    /**
     * Append a buffer object
     *
     * @param Buffer $buf
     * @param int    $indent = 0
     */
    public function appendBuffer(Buffer $buf, int $indent = 0): void
    {
        if ($indent) {
            $this->setIndent($indent);
            $lines = $buf->lines();
            foreach ($lines as $line) {
                $this->appendLine($line);
            }
        } else {
            $this->content .= $buf->__toString();
        }
    }

    /**
     * Split buffer content into lines
     *
     * @return string[] lines
     */
    public function lines(): array
    {
        return \explode("\n", $this->content);
    }

    /**
     * Output the buffer as a string
     */
    public function __toString(): string
    {
        return $this->content;
    }
}
