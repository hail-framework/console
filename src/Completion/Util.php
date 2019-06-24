<?php

namespace Hail\Console\Completion;

class Util
{
    public static function qq(string $str): string
    {
        return '"' . \addcslashes($str, '"') . '"';
    }

    public static function q(string $str): string
    {
        return "'" . \addcslashes($str, "'") . "'";
    }

    public static function array_qq(array $array): array
    {
        return \array_map('self::qq', $array);
    }

    public static function array_q(array $array): array
    {
        return \array_map('self::q', $array);
    }

    public static function array_escape_space(array $array): array
    {
        return \array_map(static function ($a) {
            return \addcslashes($a, ' ');
        }, $array);
    }

    public static function array_indent(array $lines, int $level = 1): array
    {
        $space = \str_repeat('  ', $level);

        return \array_map(static function ($line) use ($space) {
            return $space . $line;
        }, $lines);
    }
}
