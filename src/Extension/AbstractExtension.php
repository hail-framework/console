<?php
namespace Hail\Console\Extension;

use Hail\Console\Command;
use Hail\Console\Logger;

abstract class AbstractExtension
{
    protected $config;

    /**
     * @var Command
     */
    private $command;

    /**
     * init method is called when the extension is added to the pool.
     */
    abstract public function init();

    public static function isSupported(): bool
    {
        return true;
    }

    public function isAvailable(): bool
    {
        return static::isSupported();
    }

    abstract public function prepare(): void;

    abstract public function execute(): void;

    abstract public function finish(): void;

    public function getOutput()
    {
        return $this->command ?
            $this->command->getOutput() :
            Logger::getInstance();
    }

    public function getCommand()
    {
        return $this->command;
    }

    protected function getApplicationOption($key)
    {
        if (!$this->command) {
            return null;
        }

        $app = $this->command->getApplication();
        if ($app === null) {
            return null;
        }

        return $app->getOption($key);
    }

    public function getCommandOption($key)
    {
        return $this->command ?
            $this->command->getOption($key) :
            null;
    }

    protected function addOption($spec, string $desc = null, string $key = null)
    {
        if (!$this->command) {
            return null;
        }

        return $this->command->addOption($spec, $desc, $key);
    }

    /**
     * @param Command $command
     *
     * @return self
     */
    public function bind($command)
    {
        $this->command = $command;
        $this->config = $command->getApplication()->config();
        $this->init();

        return $this;
    }
}
