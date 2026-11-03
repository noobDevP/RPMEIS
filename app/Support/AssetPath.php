<?php

namespace App\Support;

class AssetPath
{
    public static function root(): string
    {
        return rtrim(public_path(), DIRECTORY_SEPARATOR);
    }

    public static function absolute(string $relativePath): string
    {
        return self::root().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
    }

    public static function ensureDirectory(string $relativePath): string
    {
        $absolute = self::absolute($relativePath);

        if (! is_dir($absolute)) {
            mkdir($absolute, 0755, true);
        }

        return $absolute;
    }
}
