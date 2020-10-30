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

    public function indent(): self
    {
        $this->indent++;
        $this->updateIndentCache();

        return $this;
    }

    /**
     * Set indent level
     *
     * @param int $indent
     */
    public function setIndent(int $indent): self
    {
        if ($this->indent !== $indent) {
            $this->indent = $indent;
            $this->updateIndentCache();
        }

        return $this;
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

    public function setIndentChar(string $char): self
    {
        $this->indentChar = $char;

        return $this;
    }

    public function getIndentChar(): string
    {
        return $this->indentChar;
    }

    public function unIndent(): self
    {
        if ($this->indent > 0) {
            $this->indent--;
            $this->updateIndentCache();
        }

        return $this;
    }

    private function updateIndentCache(): self
    {
        $this->indentCache = $this->makeIndent($this->indent);

        return $this;
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
    public function append(string $text): self
    {
        $this->content .= $text;

        return $this;
    }

    public function appendIndent(int $indent = 0): self
    {
        $this->content .= ($indent ? $this->makeIndent($indent) : $this->indentCache);

        return $this;
    }

    /**
     * Append a line with indent to the buffer
     *
     * @param string $line
     * @param int    $indent
     */
    public function appendLine(string $line, int $indent = 0): self
    {
        $this->content .= ($indent ? $this->makeIndent($indent) : $this->indentCache) . $line . "\n";

        return $this;
    }

    /**
     * Append multiple lines with indent to the buffer
     *
     * @param string[] $lines
     * @param int      $indent
     */
    public function appendLines(array $lines, int $indent = 0): self
    {
        foreach ($lines as $line) {
            $this->appendLine($line, $indent);
        }

        return $this;
    }

    /**
     * Append a string and escape with charlist
     *
     * @param string      $line
     * @param string|null $charlist
     */
    public function appendEscape(string $line, string $charlist = null): self
    {
        if ($charlist === null) {
            $this->content .= \addslashes($line);
        } else {
            $this->content .= \addcslashes($line, $charlist);
        }

        return $this;
    }

    /**
     * Append a string with double quotes
     *
     * @param string $str
     */
    public function appendQuote(string $str): self
    {
        $this->content .= '"' . \addcslashes($str, '"') . '"';

        return $this;
    }

    /**
     * Append a string with single quotes
     *
     * @param string $str
     */
    public function appendSingleQuote(string $str): self
    {
        $this->content .= "'" . \addcslashes($str, "'") . "'";

        return $this;
    }

    /**
     * Append a new line to the buffer
     */
    public function newLine(): self
    {
        $this->content .= "\n";

        return $this;
    }

    /**
     * Append a string block (multilines)
     *
     * @param string $block
     * @param int    $indent = 0
     */
    public function appendBlock(string $block, int $indent = 0): self
    {
        $lines = \explode("\n", $block);
        foreach ($lines as $line) {
            $this->appendLine($line, $indent);
        }

        return $this;
    }

    /**
     * Append a buffer object
     *
     * @param Buffer $buf
     * @param int    $indent = 0
     */
    public function appendBuffer(Buffer $buf, int $indent = 0): self
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

        return $this;
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
