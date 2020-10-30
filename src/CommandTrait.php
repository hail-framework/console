<?php
/*
 * This file is part of the CLIFramework package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hail\Console;

use Hail\Console\Logger\Logger;
use Hail\Console\Option\{
    Option,
    OptionCollection,
    OptionResult
};
use Hail\Console\Exception\{
    RequireValueException,
    CommandNotFoundException,
    CommandArgumentNotEnoughException,
    CommandClassNotFoundException
};
use Hail\Console\Component\Prompter;

/**
 * Command based class (application & subcommands inherit from this class)
 *
 * register subcommands.
 */
trait CommandTrait
{
    /**
     * @var Command[] application commands
     *
     * which is an associative array, contains command class mapping info
     *
     *     command name => command class name
     *
     * */
    protected $commands = [];
    protected $aliases = [];

    /**
     * @var CommandGroup[]
     */
    protected $commandGroups = [];

    /**
     * @var OptionResult|null parsed options
     */
    private $options;

    /**
     * @var OptionCollection|null
     */
    private $optionSpecs;

    /**
     * Parent command object. (the command caller)
     *
     * @var CommandInterface
     */
    public $parent;

    /**
     * @var Argument[]|null
     */
    private $arguments;
    private $argumentNames = [];

    protected $extensions = [];

    /**
     * Command message logger.
     *
     * @var Logger
     */
    public $logger;

    /**
     * @var Prompter|null
     */
    private $prompter;

    /**
     * Returns one line brief for this command.
     *
     * @return string brief
     */
    public function brief(): string
    {
        return 'awesome brief for your app.';
    }

    /**
     * Usage string  (one-line)
     *
     * @return string usage
     */
    public function usage(): string
    {
        return '';
    }

    /**
     * Detailed help text
     *
     * @return string helpText
     */
    public function help(): string
    {
        return '';
    }


    /**
     * Method for users to define alias.
     *
     * @return string[]
     */
    public function aliases(): array
    {
        return [];
    }

    /**
     * Translate current class name to command name.
     *
     * @return string command name
     */
    public function name(): string
    {
        static $name = null;
        if ($name === null) {
            // Extract command name from the class name.
            $class = substr(strrchr(static::class, '\\'), 1);
            $name = CommandLoader::inverseTranslate($class);
        }

        return $name;
    }

    public function getPrompter(): Prompter
    {
        if ($this->prompter === null) {
            $this->prompter = new Prompter();
        }

        return $this->prompter;
    }

    public function addCommandGroup(string $groupName, array $commands = []): CommandGroup
    {
        $group = new CommandGroup($groupName);
        foreach ($commands as $val) {
            $cmd = $this->addCommand($val);
            $group->addCommand($cmd);
        }
        $this->commandGroups[] = $group;

        return $group;
    }

    public function getCommandGroups(): array
    {
        return $this->commandGroups;
    }

    /**
     * Get the main application object from parents or the object itself.
     *
     * @return Application|null
     */
    public function getApplication(): ?Application
    {
        if ($this instanceof Application) {
            return $this;
        }

        if ($p = $this->parent) {
            return $p->getApplication();
        }

        return null;
    }

    /**
     * Users register sub-command / options /argument here.
     *
     * @code
     *
     *      function init() {
     *          // parent::init();
     *          $this->addCommand(Help::class);
     *
     *          $this->addOption('v|verbose','Verbose messages');
     *          $this->addOption('d|debug',  'Debug messages');
     *          $this->addOption('level:',  'Level takes a value.');
     *
     *          $this->addArgument('verbose','Verbose messages');
     *          $this->addArgument('debug',  'Debug messages');
     *      }
     */
    public function init(): void
    {
        CommandLoader::autoload($this);
    }

    public function setParent(CommandInterface $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): CommandInterface
    {
        return $this->parent;
    }

    /**
     * Register a command to application, in init() method stage,
     * we save command classes in property `commands`.
     *
     * When command is needed, get the command from property `commands`, and
     * initialize the command object.
     *
     * class name could be full-qualified or subclass name (under App\Command\ )
     *
     * @param string $class Full-qualified Class name
     *
     * @return CommandInterface Loaded class name
     * @throws CommandClassNotFoundException
     */
    public function addCommand(string $class = null): CommandInterface
    {
        $realClass = CommandLoader::load($class);
        if ($realClass === null) {
            throw new CommandClassNotFoundException($class);
        }

        // register command to table
        $cmd = $this->createCommand($realClass);
        $this->connectCommand($cmd);

        return $cmd;
    }

    public function getAllCommandPrototype(): array
    {
        $lines = [];

        if (method_exists($this, 'execute')) {
            $lines[] = $this->getCommandPrototype();
        }

        if ($this->hasCommands()) {
            foreach ($this->commands as $name => $subcmd) {
                $lines[] = $subcmd->getCommandPrototype();
            }
        }

        return $lines;
    }

    public function getCommandPrototype(): string
    {
        $out = [];

        $out[] = basename($this->getApplication()->getProgramName());

        foreach ($this->getCommandNameTraceArray() as $n) {
            $out[] = $n;
        }

        if (!empty($this->getOptionCollection()->options)) {
            $out[] = '[options]';
        }
        if ($this->hasCommands()) {
            $out[] = '<subcommand>';
        } else {
            foreach ($this->getArguments() as $argument) {
                $out[] = '<' . $argument->name() . '>';
            }
        }

        return \implode(' ', $out);
    }


    /**
     * connectCommand connects a command name with a command object.
     *
     * @param CommandInterface $cmd
     */
    public function connectCommand(CommandInterface $cmd)
    {
        $name = $cmd->name();
        $this->commands[$name] = $cmd;

        // register command aliases to the alias table.
        foreach ($cmd->aliases() as $alias) {
            $this->aliases[$alias] = $cmd;
        }
    }

    public function aggregate(): array
    {
        $commands = $this->getVisibleCommands();

        $dev = null;
        foreach ($this->commandGroups as $index => $g) {
            if ($g->isHidden) {
                continue;
            }

            foreach ($g->getCommands() as $name => $cmd) {
                unset($commands[$name]);
            }

            if ($g->getId() === 'dev') {
                $dev = $g;
                unset($this->commandGroups[$index]);
            }
        }
        $this->commandGroups[] = $dev;

        return [
            'groups' => $this->commandGroups,
            'commands' => $commands,
        ];
    }

    public function hasCommands(): bool
    {
        return !empty($this->commands);
    }

    public function hasCommand(string $command): bool
    {
        return isset($this->commands[$command]) || isset($this->aliases[$command]);
    }

    public function getCommandList(): array
    {
        return \array_keys($this->commands);
    }

    public function getVisibleCommands(): array
    {
        $commands = [];
        foreach ($this->commands as $name => $command) {
            if ($name[0] === '_') {
                continue;
            }

            $commands[$name] = $command;
        }

        return $commands;
    }

    public function getVisibleCommandList(): array
    {
        return \array_keys($this->getVisibleCommands());
    }

    public function getCommandNameTraceArray(): array
    {
        $cmdStacks = [$this->name()];
        $p = $this->parent;
        while ($p) {
            if (!$p instanceof Application) {
                $cmdStacks[] = $p->name();
            }
            $p = $p->parent;
        }

        return\array_reverse($cmdStacks);
    }

    public function getSignature(): string
    {
        return \implode('.', $this->getCommandNameTraceArray());
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getCommand(string $command): CommandInterface
    {
        if (isset($this->aliases[$command])) {
            return $this->aliases[$command];
        }

        if (isset($this->commands[$command])) {
            return $this->commands[$command];
        }

        throw new CommandNotFoundException($this, $command);
    }

    public function guessCommand(string $commandName): string
    {
        // array of words to check against
        $words = \array_keys($this->commands);

        return Corrector::correct($commandName, $words);
    }

    public function createCommand(string $class): CommandInterface
    {
        if (!\is_a($class, CommandInterface::class, true)) {
            throw new CommandClassNotFoundException($class);
        }

        $cmd = new $class($this);
        $cmd->init();

        return $cmd;
    }

    public function setLogger(Logger $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function getOutput(): Logger
    {
        return $this->logger;
    }

    /**
     * @param string|Option $spec
     * @param string|null   $desc
     * @param string|null   $key
     *
     * @return Option
     */
    public function addOption($spec, string $desc = null, string $key = null): Option
    {
        $option = $this->getOptionCollection();

        return $option->add($spec, $desc, $key);
    }

    public function getOption(string $key)
    {
        return ($this->options && $this->options->has($key)) ? $this->options->get($key) : null;
    }

    /**
     * Get Option Results
     *
     * @return OptionResult command options object (parsed, and a option results)
     */
    public function getOptions(): OptionResult
    {
        return $this->options;
    }

    /**
     * Set option results
     *
     * @param OptionResult $options
     */
    public function setOptions(OptionResult $options): void
    {
        $this->options = $options;
    }

    /**
     * Get Command-line Option spec
     *
     * @return OptionCollection
     */
    public function getOptionCollection(): OptionCollection
    {
        // get option parser, init specs from the command.
        if (!$this->optionSpecs) {
            $this->optionSpecs = new OptionCollection;
        }

        return $this->optionSpecs;
    }

    /**
     * Prepare stage method
     */
    public function prepare(): void
    {
        foreach ($this->extensions as $extension) {
            $extension->prepare();
        }
    }

    /**
     * Finalize stage method
     */
    public function finish(): void
    {
        foreach ($this->extensions as $extension) {
            $extension->finish();
        }
    }

    public function addArgument(string $name, string $desc = null): Argument
    {
        if ($this->arguments === null) {
            $this->arguments = [];
        }

        $argument = new Argument($name, $desc);
        $this->arguments[] = $argument;
        $this->argumentNames[$argument->name] = $argument;

        return $argument;
    }

    /**
     * @param int|string $key
     *
     * @return Argument|null
     */
    public function findArgument($key): ?Argument
    {
        $arguments = $this->getArguments();

        return $this->argumentNames[$key] ?? $arguments[$key] ?? null;
    }

    /**
     * @param int|string $key
     *
     * @return mixed|null
     */
    public function getArgument($key)
    {
        $argument = $this->findArgument($key);
        if ($argument === null) {
            return null;
        }

        return $argument->getValue();
    }

    public function getArguments(): array
    {
        // if user not define any arguments, get argument info from method parameters
        if ($this->arguments === null) {
            $this->arguments = [];

            $ro = new \ReflectionObject($this);
            $method = $ro->getMethod('execute');
            $parameters = $method->getParameters();

            foreach ($parameters as $param) {
                $a = $this->addArgument($param->getName());
                if ($param->isOptional()) {
                    $a->optional();

                    if ($param->isDefaultValueAvailable()) {
                        $a->setValue($param->getDefaultValue());
                    }
                }
            }
        }

        return $this->arguments;
    }

    /**
     * Execute command object, this is a wrapper method for execution.
     *
     * In this method, we check the command arguments by the Reflection feature
     * provided by PHP.
     *
     * @param array $args command argument list (not associative array).
     *
     * @throws CommandArgumentNotEnoughException
     * @throws RequireValueException
     * @throws \ReflectionException
     */
    public function executeWrapper(array $args): void
    {
        // Validating arguments
        foreach ($this->getArguments() as $k => $argument) {
            if (!isset($args[$k])) {
                if ($argument->isRequired()) {
                    throw new RequireValueException("Argument pos {$k} '{$argument->name()}' requires a value.");
                }

                continue;
            }

            if (!$argument->validate($args[$k])) {
                $this->logger->error("Invalid argument {$args[$k]}");

                return;
            }

            $args[$k] = $argument->getValue();
        }

        $refMethod = new \ReflectionMethod($this, 'execute');
        $requiredNumber = $refMethod->getNumberOfRequiredParameters();

        $count = \count($args);
        if ($count < $requiredNumber) {
            throw new CommandArgumentNotEnoughException($this, $count, $requiredNumber);
        }

        foreach ($this->extensions as $extension) {
            $extension->execute();
        }

        $this->execute(...$args);
    }
}
