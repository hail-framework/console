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

use Hail\Console\Option\{
    ContinuousOptionParser, OptionResult
};
use Hail\Console\Exception\{
    CommandNotFoundException, CommandArgumentNotEnoughException
};
use Hail\Console\Command\{
    Help, ZshCompletion, BashCompletion, Meta, Phar
};
use Psr\Log\LogLevel;
use Hail\Console\Logger\Logger;

class Application implements CommandInterface
{
    use CommandTrait;

    public const NAME = 'Hail-Console';

    /**
     * timestamp when started
     */
    public $startedAt;

    public $programName;

    /**
     * @var array
     */
    protected $config;
    protected $loader;

    public function __construct(array $config)
    {
        // The empty option result will be replaced with the parsed option result.
        $this->setOptions(new OptionResult());
        $this->setLogger(Logger::getInstance());
        $this->config = $config;
    }

    /**
     * @return string brief of this application
     */
    public function brief(): string
    {
        return 'Hail Console';
    }

    public function help(): string
    {
        $name = \basename($this->getProgramName());

        return \wordwrap(
            "'$name help' lists available subcommands. See $name help <command> to read about a specific subcommand or $name.",
            70
        );
    }

    public function init(): void
    {
        $this->addCommand(Help::class);
        $this->addCommandGroup('Development Commands', [
            ZshCompletion::class,
            BashCompletion::class,
            Phar::class,
            Meta::class,
        ])->setId('dev');

        $commands = $this->config('commands', []);
        if ($commands !== []) {
            foreach ($commands as $command) {
                $this->addCommand($command);
            }
        }

        $this->addOption('v|verbose', 'Print verbose message.');
        $this->addOption('d|debug', 'Print debug message.');
        $this->addOption('q|quiet', 'Be quiet.');
        $this->addOption('h|help', 'Show help.');
        $this->addOption('version', 'Show version.');

        $this->addOption('p|profile', 'Display timing and memory usage information.');
        $this->addOption('log-path?', 'The path of a log file.');
        $this->addOption('no-interact', 'Do not ask any interactive question.');
    }

    /**
     * Execute `run` method with a default try & catch block to catch the exception.
     *
     * @param array $argv
     *
     * @return bool return true for success, false for failure. the returned
     *              state will be reflected to the exit code of the process.
     */
    public function runWithTry(array $argv)
    {
        try {
            return $this->run($argv);
        } catch (CommandArgumentNotEnoughException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->writeln('Expected argument prototypes:');
            foreach ($e->getCommand()->getAllCommandPrototype() as $p) {
                $this->logger->writeln("\t" . $p);
            }
            $this->logger->newline();
        } catch (CommandNotFoundException $e) {
            $this->logger->error($e->getMessage() . ' available commands are: ' .
                implode(', ', $e->getCommand()->getVisibleCommandList())
            );
            $this->logger->newline();

            $this->logger->writeln('Please try the command below to see the details:');
            $this->logger->newline();
            $this->logger->writeln("\t" . $this->getProgramName() . ' help ');
            $this->logger->newline();
        } catch (\BadMethodCallException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error('Seems like an application logic error, please contact the developer.');
        } catch (\Throwable $e) {
            ExceptionPrinter::dump($e, $this->getOption('debug') ?? false);
        }

        return false;
    }

    /**
     * Run application with
     * list argv
     *
     * @param array $argv
     *
     * @return bool return true for success, false for failure. the returned
     *              state will be reflected to the exit code of the process.
     *
     * @throws CommandArgumentNotEnoughException
     * @throws CommandNotFoundException
     * @throws Exception\InvalidOptionException
     * @throws Exception\RequireValueException
     * @throws \ReflectionException
     */
    public function run(array $argv): bool
    {
        $this->setProgramName($argv[0]);

        $current = $this;

        // init application,
        // before parsing options, we have to known the registered commands.
        $current->init();

        // parse application options
        $parser = new ContinuousOptionParser($current->getOptionCollection());

        // parse the first part options (options after script name)
        // option parser should stop before next command name.
        //
        //    $ app.php -v -d next
        //                  |
        //                  |->> parser
        //
        //
        $current->setOptions(
            $parser->parse($argv)
        );

        if (false === $current->prepare()) {
            return false;
        }

        $commandStack = $arguments = [];

        // build the command list from command line arguments
        while (!$parser->isEnd()) {
            $a = $parser->getCurrentArgument();

            // if current command is in subcommand list.
            if ($current->hasCommands()) {

                if (!$current->hasCommand($a)) {
                    if (!$this->getOption('no-interact') && ($guess = $current->guessCommand($a)) !== null) {
                        $a = $guess;
                    } else {
                        throw new CommandNotFoundException($current, $a);
                    }
                }

                $parser->advance(); // advance position

                // get command object of "$a"
                $nextCommand = $current->getCommand($a);

                $parser->setSpecs($nextCommand->getOptionCollection());

                // parse the option result for command.
                $nextCommand->setOptions(
                    $parser->continueParse()
                );

                $commandStack[] = $current = $nextCommand; // save command object into the stack
            } else {
                $r = $parser->continueParse();

                if (\count($r)) {
                    // get the option result and merge the new result
                    $current->getOptions()->merge($r);
                } else {
                    $a = $parser->advance();
                    $arguments[] = $a;
                }
            }
        }

        foreach ($commandStack as $cmd) {
            if (false === $cmd->prepare()) {
                return false;
            }
        }

        // get last command and run
        if ($lastCommand = array_pop($commandStack)) {
            $lastCommand->executeWrapper($arguments);
            $lastCommand->finish();
            while ($cmd = array_pop($commandStack)) {
                // call finish stage.. of every command.
                $cmd->finish();
            }
        } else {
            // no command specified.
            $this->executeWrapper($arguments);

            return true;
        }

        $current->finish();
        $this->finish();

        return true;
    }

    /**
     * This is a `before` trigger of an app. when the application is getting
     * started, we run `prepare` method to prepare the settings.
     */
    public function prepare(): void
    {
        $this->startedAt = \microtime(true);

        if ($this->getOption('debug')) {
            $this->logger->setLevel(LogLevel::DEBUG);
        } elseif ($this->getOption('verbose')) {
            $this->logger->setLevel(LogLevel::INFO);
        } elseif ($this->getOption('quiet')) {
            $this->logger->setLevel(LogLevel::ERROR);
        } elseif ($this->config('debug', false)) {
            $this->logger->setLevel(LogLevel::DEBUG);
        } elseif ($this->config('verbose', false)) {
            $this->logger->setLevel(LogLevel::INFO);
        }
    }

    public function finish(): void
    {
        if ($this->getOption('profile')) {
            $this->logger->notice(
                \sprintf('Memory usage: %.2fMB (peak: %.2fMB), time: %.4fs',
                    \memory_get_usage(true) / (1024 * 1024),
                    \memory_get_peak_usage(true) / (1024 * 1024),
                    \microtime(true) - $this->startedAt
                )
            );
        }
    }

    public function setProgramName(string $programName): self
    {
        $this->programName = $programName;

        return $this;
    }

    public function getProgramName(): string
    {
        return $this->programName;
    }

    public function name(): string
    {
        return static::NAME;
    }

    /**
     * This method is the top logic of an application. when there is no
     * argument provided, we show help content by default.
     *
     * @param array ...$arguments
     *
     * @throws CommandArgumentNotEnoughException
     * @throws CommandNotFoundException
     * @throws Exception\RequireValueException
     * @throws \ReflectionException
     */
    public function execute(...$arguments): void
    {
        $options = $this->getOptions();

        // show list and help by default
        $help = $this->getCommand('help');
        $help->setOptions($options);
        if ($help || $options->help) {
            $help->executeWrapper($arguments);

            return;
        }

        throw new CommandNotFoundException($this, 'help');
    }

    public function config(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? $default;
    }
}
