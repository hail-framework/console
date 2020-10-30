<?php

namespace Hail\Console;

use Hail\Console\Logger\Logger;
use Throwable;

class ExceptionPrinter
{
    public static function dumpVar($var): string
    {
        return self::output($var);
    }

    public static function dumpArgs(array $args): string
    {
        if (empty($args)) {
            return '';
        }

        $desc = [];
        foreach ($args as $a) {
            $desc[] = self::output($a);
        }

        return \implode(', ', $desc);
    }

    public static function dumpTraceInPhar(Throwable $e): void
    {
        $logger = Logger::getInstance();

        $logger->notice("Trace:\n");
        $trace = $e->getTrace();
        foreach ($trace as $idx => $entry) {
            $argDesc = static::dumpArgs($entry['args']);
            $logger->notice(sprintf('    %d) %s%s%s(%s)', $idx, @$entry['class'], @$entry['type'],
                $entry['function'], $argDesc));
        }
        $logger->newline();
    }

    public static function dumpTrace(Throwable $e): void
    {
        $logger = Logger::getInstance();

        $logger->notice("Trace:\n");
        $trace = $e->getTrace();
        foreach ($trace as $idx => $entry) {
            $argDesc = static::dumpArgs($entry['args']);

            $logger->notice(sprintf('    %d) %s%s%s(%s)', $idx, @$entry['class'], @$entry['type'],
                $entry['function'], $argDesc));

            if (isset($entry['file'], $entry['line'])) {
                $logger->notice(sprintf('        from %s: %d', $entry['file'], $entry['line']));
            }

            $logger->newline();
        }

        $logger->newline();
    }

    public static function dumpCodeBlock(Throwable $e)
    {
        $line = $e->getLine();
        $file = $e->getFile();

        $logger = Logger::getInstance();

        $logger->notice("Thrown from $file at line $line:\n");

        $lines = file($file);
        $indexRange = range(max($line - 4, 0), min($line + 3, count($lines)));
        foreach ($indexRange as $index) {
            if ($index === ($line - 1)) {
                $logger->warning(sprintf('> % 3d', $index + 1) . rtrim($lines[$index]));
            } else {
                $logger->notice(sprintf('  % 3d', $index + 1) . rtrim($lines[$index]));
            }
        }

        $logger->newline();
    }

    public static function dumpBrief(Throwable $e): void
    {
        $code = $e->getCode();
        $message = $e->getMessage();

        // $file = $e->getFile();
        // $line = $e->getLine();

        $class = get_class($e);

        $error = $code ? "$class: ($code) $message" : "$class: $message";
        Logger::getInstance()->error($error);
    }

    public static function dump(Throwable $e, bool $debug = false)
    {
        static::dumpBrief($e);

        if ($debug) {
            static::dumpCodeBlock($e);
            static::dumpTrace($e);
        } else {
            static::dumpTraceInPhar($e);
        }
    }

    protected static function output($a): string
    {
        if (\is_array($a)) {
            $out = '';
            if (!self::arrayIsAssoc($a)) {
                foreach ($a as $i) {
                    $out .= self::output($i) . ', ';
                }
            } else {
                foreach ($a as $k => $i) {
                    $out .= $k . ' => ' . self::output($i) . ', ';
                }
            }

            return '[' . \substr($out, 0, -2) . ']';
        }

        if (\is_scalar($a)) {
            return \var_export($a, true);
        }

        if (\is_object($a)) {
            if (\method_exists($a, '__toString')) {
                return $a->__toString();
            }

            return \get_class($a);
        }

        return '...';
    }

    public static function arrayIsAssoc(array $array): bool
    {
        if (!isset($array[0])) {
            return true;
        }

        $keys = \array_keys($array);

        return $keys !== \array_keys($keys);
    }
}
