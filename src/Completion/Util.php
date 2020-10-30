<?php

namespace Hail\Console\Completion;

class Util
{
    public static function qq(string $str): string
    {
        return '"' . \addcslashes($str, '"') . '"';
    }

    public static function array_qq(array $array): array
    {
        return \array_map('self::qq', $array);
    }
}
