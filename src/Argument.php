<?php

namespace Hail\Console;

class Argument
{
    public $name;

    public $desc;

    public $isa;
    protected $isaOption;

    /**
     * @var bool
     */
    public $optional = false;

    /**
     * @var bool
     */
    public $multiple = false;

    /**
     * @var array
     */
    public $suggestions;

    /**
     * @var array
     */
    public $validValues;

    /**
     * @var callable
     */
    protected $validator;

    /**
     * @var string file/path glob pattern
     */
    public $glob;

    protected $value;

    public function __construct(string $spec, string $desc = null)
    {
        $this->initFromSpecString($spec);

        if ($desc) {
            $this->desc = $desc;
        }
    }

    /**
     * Build spec attributes from spec string.
     *
     * @param string $specString
     * @throws \InvalidArgumentException
     */
    protected function initFromSpecString($specString)
    {
        $pattern = '/
        ([a-zA-Z0-9-]+)

        # option attribute operators
        ([+?])?

        # value types
        (?:=(bool|boolean|string|int|number|date|datetime|file|dir|url|email|ip|ipv6|ipv4))?
        /x';
        $ret = \preg_match($pattern, $specString, $regs);
        if ($ret === false || $ret === 0) {
            throw new \InvalidArgumentException('Incorrect spec string');
        }

        $this->name = $regs[1];
        $attributes = $regs[2] ?? null;
        $type = $regs[3] ?? null;


        // option is required.
        if (\strpos($attributes, '+') !== false) {
            // option with multiple value
            $this->multiple();
        } elseif (\strpos($attributes, '?') !== false) {
            // option is optional.(zero or one value)
            $this->optional();
        }

        if ($type) {
            $this->isa($type);
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isa(string $type, $option = null): self
    {
        if ($type === 'bool') {
            $type = 'boolean';
        }
        $this->isa = $type;
        $this->isaOption = $option;

        return $this;
    }

    public function desc(string $desc): self
    {
        $this->desc = $desc;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->optional === false;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function optional(): self
    {
        $this->optional = true;

        return $this;
    }

    public function multiple(bool $is = true): self
    {
        $this->multiple = $is;

        return $this;
    }

    public function validValues(array $val): self
    {
        $this->validValues = $val;

        return $this;
    }

    public function validator(callable $fun): self
    {
        $this->validator = $fun;

        return $this;
    }

    /**
     * Assign suggestions
     *
     * @param string[] $values
     *
     * @return self
     */
    public function suggestions(array $values): self
    {
        $this->suggestions = $values;

        return $this;
    }


    /**
     * Specify argument glob pattern
     */
    public function glob(string $glob): self
    {
        $this->glob = $glob;

        return $this;
    }


    public function getSuggestions(): array
    {
        return $this->suggestions ?? [];
    }


    public function getValidValues(): array
    {
        return $this->validValues ?? [];
    }

    /**
     * @param mixed $value
     *
     * @return array|bool
     */
    public function validate($value)
    {
        if ($this->isa) {
            $test = ValueType::test($value, $this->isa, $this->isaOption);
            if ($test === false) {
                return false;
            }

            if ($test === true) {
                $value = ValueType::parse();
            }
        }

        if (
            ($values = $this->getValidValues()) &&
            !\in_array($value, $values, true)
        ) {
            return false;
        }

        if (
            $this->validator &&
            !($this->validator)($value)
        ) {
            return false;
        }

        $this->value = $value;

        return true;
    }

    public function setValue($value): self
    {
        $this->validate($value);

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }
}
