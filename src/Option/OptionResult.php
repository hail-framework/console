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

/**
 * Define the getopt parsing result.
 */
class OptionResult implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var array option specs, key => Option object
     * */
    public $keys = [];

    public $arguments = [];

    public function getIterator(): iterable
    {
        return new \ArrayIterator($this->keys);
    }

    public function count(): int
    {
        return \count($this->keys);
    }

    public function merge(OptionResult $a): void
    {
        $this->keys = array_merge($this->keys, $a->keys);
        $this->arguments = array_merge($this->arguments, $a->arguments);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __get(string $key)
    {
        return $this->get($key);
    }

    public function get(string $key)
    {
        if (isset($this->keys[$key])) {
            return $this->keys[$key]->getValue();
        }

        // verifying if we got a camelCased key: http://stackoverflow.com/a/7599674/102960
        // get $options->baseDir as $option->{'base-dir'}
        $parts = \preg_split('/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', $key);
        if (\count($parts) > 1) {
            $key = \implode('-', \array_map('\strtolower', $parts));
        }

        if (isset($this->keys[$key])) {
            return $this->keys[$key]->getValue();
        }

        return null;
    }

    public function __set(string $key, Option $value): void
    {
        $this->set($key, $value);
    }

    public function has(string $key): bool
    {
        return isset($this->keys[$key]);
    }

    public function set(string $key, Option $value): self
    {
        $this->keys[$key] = $value;

        return $this;
    }

    public function addArgument(Argument $arg): self
    {
        $this->arguments[] = $arg;

        return $this;
    }

    public function getArguments(): array
    {
        return \array_map('\strval', $this->arguments);
    }

    public function offsetSet($name, $value)
    {
        return $this->set($name, $value);
    }

    public function offsetExists($name)
    {
        return $this->has($name);
    }

    public function offsetGet($name)
    {
        return $this->get($name);
    }

    public function offsetUnset($name)
    {
        throw new \RuntimeException('Options can not unset');
    }

    public function toArray(): array
    {
        $array = [];
        foreach ($this->keys as $key => $option) {
            $array[$key] = $option->getValue();
        }

        return $array;
    }
}
