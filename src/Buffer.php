<?php
namespace Hail\Console;

class Buffer
{
    public $content = '';

    public $indent = 0;

    protected $indentCache = '';

    public $indentChar = '  ';

    public $format;

    public const FORMAT_UNIX = 0;
    public const FORMAT_DOS = 1;

    public $newline = "\n";

    public function __construct(string $content = '')
    {
        $this->content = $content;
        $this->format = self::FORMAT_UNIX;
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

    public function unindent(): void
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
     * @param int $indent
     */
    public function appendLine(string $line, int $indent = 0): void
    {
        $this->content .= ($indent ? $this->makeIndent($indent) : $this->indentCache) . $line . $this->newline;
    }


    /**
     * Append multiple lines with indent to the buffer
     *
     * @param string[] $lines
     * @param int $indent
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
     * @param string $line
     * @param string $charlist
     */
    public function appendEscape(string $line, string $charlist): void
    {
        $this->content .= \addcslashes($line, $charlist);
    }

    /**
     * Append a string with addslashes function to the buffer
     *
     * @param string $line
     */
    public function appendEscapeSlash(string $line): void
    {
        $this->content .= \addslashes($line);
    }

    /**
     * Append a string with double quotes
     *
     * @param string $str
     */
    public function appendQuoteString(string $str): void
    {
        $this->content .= '"' . \addcslashes($str, '"') . '"';
    }


    /**
     * Append a string with single quotes
     *
     * @param string $str
     */
    public function appendSingleQuoteString(string $str): void
    {
        $this->content .= "'" . \addslashes($str) . "'";
    }


    /**
     * Append a new line to the buffer
     */
    public function newLine(): void
    {
        $this->content .= $this->newline;
    }


    /**
     * Set line format
     *
     * @param int $format Buffer::FORMAT_UNIX or Buffer::FORMAT_DOS
     */
    public function setFormat(int $format): void
    {

        if ($format === self::FORMAT_UNIX) {
            $this->newline = "\n";
        } elseif ($format === self::FORMAT_DOS) {
            $this->newline = "\r\n";
        } else {
            throw new \InvalidArgumentException('format not support');
        }

        $this->format = $format;
    }


    /**
     * Append a string block (multilines)
     *
     * @param string $block
     * @param int $indent = 0
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
     * @param int $indent = 0
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
        return \explode($this->newline, $this->content);
    }

    /**
     * Output the buffer as a string
     */
    public function __toString(): string
    {
        return $this->content;
    }
}
