<?php
/*
 * This file is part of the GetOptionKit package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Hail\Console\Option;

class Argument
{
    public $arg;

    public function __construct(string $arg)
    {
        $this->arg = $arg;
    }

    public function isLongOption(): bool
    {
        return \strpos($this->arg, '--') === 0;
    }

    public function isShortOption(): bool
    {
        return $this->arg[0] === '-' && $this->arg[1] !== '-';
    }

    public function isEmpty(): bool
    {
        return '' === $this->arg;
    }

    /**
     * Check if an option is one of the option in the collection.
     */
    public function anyOfOptions(OptionCollection $options): bool
    {
        $name = $this->getOptionName();
        $keys = $options->keys();

        return \in_array($name, $keys, true);
    }

    /**
     * Check current argument is an option by the preceding dash.
     * note this method does not work for string with negative value.
     *
     *   -a
     *   --foo
     */
    public function isOption(): bool
    {
        return $this->isShortOption() || $this->isLongOption();
    }

    /**
     * Parse option and return the name after dash. e.g.,
     * '--foo' returns 'foo'
     * '-f' returns 'f'.
     *
     * @return string|null
     */
    public function getOptionName(): ?string
    {
        if (\preg_match('/^[-]+([a-zA-Z0-9-]+)/', $this->arg, $regs)) {
            return $regs[1];
        }

        return null;
    }

    public function splitAsOption(): array
    {
        return \explode('=', $this->arg, 2);
    }

    public function containsOptionValue(): bool
    {
        return \preg_match('/=.+/', $this->arg) === 1;
    }

    public function getOptionValue(): ?string
    {
        if (\preg_match('/=(.+)/', $this->arg, $regs)) {
            return $regs[1];
        }

        return null;
    }

    /**
     * Check combined short flags for "-abc" or "-vvv".
     *
     * like: -abc
     */
    public function withExtraFlagOptions(): bool
    {
        return \preg_match('/^-[a-zA-Z0-9]{2,}/', $this->arg) === 1;
    }

    public function extractExtraFlagOptions(): array
    {
        $args = [];
        for ($i = 2, $len = \strlen($this->arg); $i < $len; ++$i) {
            $args[] = '-' . $this->arg[$i];
        }
        $this->arg = \substr($this->arg, 0, 2); # -[a-z]

        return $args;
    }

    public function __toString(): string
    {
        return $this->arg;
    }
}
