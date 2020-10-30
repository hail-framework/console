<?php
/**
 * This file is part of the CLIFramework package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hail\Console;

use Hail\Console\Exception\ExtensionException;
use Hail\Console\Extension\AbstractExtension;
use Hail\Console\Logger\Logger;
use Hail\Console\Option\OptionResult;

/**
 * abstract command class
 *
 */
abstract class Command implements CommandInterface
{
    use CommandTrait;

    /**
     * @var Application Application object.
     */
    public $application;

    public function __construct($parent = null)
    {
        // this variable is optional (for backward compatibility)
        if ($parent) {
            $this->setParent($parent);
        }

        // create an empty option result, please note this result object will
        // be replaced with the parsed option result.
        $this->setOptions(new OptionResult());

        $this->setLogger(Logger::getInstance());
    }

    /**
     * Get the main application object from parents
     *
     * @return Application|null
     */
    public function getApplication(): ?Application
    {
        if ($this->application) {
            return $this->application;
        }

        if ($p = $this->parent) {
             return $this->application = $p->getApplication();
        }

        return null;
    }

    /**
     * Register and bind the extension
     *
     * @param AbstractExtension $extension
     *
     * @throws ExtensionException
     */
    public function addExtension(AbstractExtension $extension): self
    {
        if (!$extension->isAvailable()) {
            throw new ExtensionException('Extension ' . \get_class($extension) . ' is not available', $extension);
        }

        $this->extensions[] = $extension->bind($this);

        return $this;
    }

    /**
     * method `extension` is an alias of addExtension
     *
     * @param AbstractExtension|string $extension
     *
     * @throws ExtensionException
     */
    public function extension($extension): void
    {
        if (\is_string($extension)) {
            if (!is_a($extension, AbstractExtension::class, true)) {
                throw new ExtensionException('Not an extension class name.');
            }

            $extension = new $extension();
        } elseif (!$extension instanceof AbstractExtension) {
            throw new ExtensionException('Not an extension object.');
        }

        $this->addExtension($extension);
    }
}
