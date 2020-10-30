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

use Hail\Console\Exception\RequireValueException;
use Hail\Console\Logger\Logger;
use Hail\Console\Option\Option;
use Hail\Console\Option\OptionCollection;
use Hail\Console\Option\OptionResult;
use Hail\Console\Component\Prompter;
use Hail\Console\Exception\CommandNotFoundException;
use Hail\Console\Exception\CommandArgumentNotEnoughException;
use Hail\Console\Exception\CommandClassNotFoundException;

interface CommandInterface
{
    /**
     * Returns one line brief for this command.
     *
     * @return string brief
     */
    public function brief(): string;

    /**
     * Usage string  (one-line)
     *
     * @return string usage
     */
    public function usage(): string;

    /**
     * Detailed help text
     *
     * @return string helpText
     */
    public function help(): string;


    /**
     * Method for users to define alias.
     *
     * @return string[]
     */
    public function aliases(): array;

    /**
     * Translate current class name to command name.
     *
     * @return string command name
     */
    public function name(): string;

    public function getPrompter(): Prompter;

    /**
     * Add a command group and register the commands automatically
     *
     * @param string $groupName The group name
     * @param array  $commands  Command array combines indexed command names or command class assoc array.
     *
     * @return CommandGroup
     * @throws CommandClassNotFoundException
     */
    public function addCommandGroup(string $groupName, array $commands = []): CommandGroup;

    public function getCommandGroups(): array;

    /**
     * Get the main application object from parents or the object itself.
     *
     * @return Application|null
     */
    public function getApplication(): ?Application;

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
    public function init(): void;

    public function setParent(CommandInterface $parent): self;

    public function getParent(): CommandInterface;

    /**
     * Register a command to application, in init() method stage,
     * we save command classes in property `commands`.
     *
     * When command is needed, get the command from property `commands`, and
     * initialize the command object.
     *
     * class name could be full-qualified or subclass name (under App\Command\ )
     *
     * @param string|null $class Full-qualified Class name
     *
     * @return Command Loaded class name
     * @throws CommandClassNotFoundException
     */
    public function addCommand(string $class = null): Command;


    /**
     * getAllCommandPrototype() method is used for returning command prototype in string.
     * Very useful when user entered command with wrong argument or format.
     */
    public function getAllCommandPrototype(): array;

    public function getCommandPrototype(): string;


    /**
     * connectCommand connects a command name with a command object.
     *
     * @param CommandInterface $cmd
     */
    public function connectCommand(CommandInterface $cmd);


    /**
     * Aggregate command info
     */
    public function aggregate(): array;


    /**
     * Return true if this command has subcommands.
     *
     * @return bool
     */
    public function hasCommands(): bool;

    /**
     * Check if a command name is registered in this application / command object.
     *
     * @param string $command command name
     *
     * @return bool
     */
    public function hasCommand(string $command): bool;

    /**
     * Get command name list
     *
     * @return array command name list
     */
    public function getCommandList(): array;

    /**
     * Some commands are not visible. when user runs 'help', we should just
     * show them these visible commands
     *
     * @return string[] CommandBase command map
     */
    public function getVisibleCommands(): array;

    /**
     * Command names start with understore are hidden command. we ignore the commands.
     *
     * @return string[]
     */
    public function getVisibleCommandList(): array;


    /**
     * Return the command name stack
     *
     * @return string[]
     */
    public function getCommandNameTraceArray(): array;

    public function getSignature(): string;


    /**
     * Return the objects of all sub commands.
     *
     * @return Command[]
     */
    public function getCommands(): array;

    /**
     * Get subcommand object from current command
     * by command name.
     *
     * @param string $command
     *
     * @return CommandInterface initialized command object.
     * @throws CommandNotFoundException
     */
    public function getCommand(string $command): CommandInterface;

    public function guessCommand(string $commandName): string;

    /**
     * Create and initialize command object.
     *
     * @param string $class Command class.
     *
     * @return CommandInterface command object.
     * @throws CommandClassNotFoundException
     */
    public function createCommand(string $class): CommandInterface;

    public function setLogger(Logger $logger): self;

    public function getOutput(): Logger;

    /**
     * @param string|Option $spec
     * @param string|null   $desc
     * @param string|null   $key
     *
     * @return Option
     */
    public function addOption($spec, string $desc = null, string $key = null): Option;

    public function getOption(string $key);

    /**
     * Get Option Results
     *
     * @return OptionResult command options object (parsed, and a option results)
     */
    public function getOptions(): OptionResult;

    /**
     * Set option results
     *
     * @param OptionResult $options
     */
    public function setOptions(OptionResult $options);

    /**
     * Get Command-line Option spec
     *
     * @return OptionCollection
     */
    public function getOptionCollection(): OptionCollection;

    /**
     * Prepare stage method
     */
    public function prepare(): void;

    /**
     * Finalize stage method
     */
    public function finish(): void;

    public function addArgument(string $name, string $desc = null): Argument;

    /**
     * @param int|string $key
     *
     * @return Argument|null
     */
    public function findArgument($key): ?Argument;

    /**
     * @param int|string $key
     *
     * @return mixed|null
     */
    public function getArgument($key);

    /**
     * Return the defined argument info objects.
     *
     * @return Argument[]
     */
    public function getArguments(): array;

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
    public function executeWrapper(array $args): void;

    public function execute(): void;
}
