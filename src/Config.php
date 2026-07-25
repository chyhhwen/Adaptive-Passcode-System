<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Reads configuration from .env.
 *
 * Previously .env existed but nothing read it, while credentials were hardcoded
 * as config("root", "", ...) in several files. This class is now the only place
 * configuration enters the application.
 */
final class Config
{
    private static ?array $values = null;

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException(
                "Environment file not found: {$path}. Copy .env.example to .env and fill it in."
            );
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Environment file is not readable: {$path}");
        }

        // .env files conventionally comment with '#', but the INI parser only
        // recognises ';'. Left as-is, a line like "#format name=value" would be
        // parsed as a real setting. Strip those lines first.
        $withoutHashComments = preg_replace('/^\s*#.*$/m', '', $contents);

        // INI_SCANNER_TYPED matters: without it "APP_DEBUG=false" parses to the
        // empty string rather than boolean false.
        $parsed = parse_ini_string((string) $withoutHashComments, false, INI_SCANNER_TYPED);

        if ($parsed === false) {
            throw new RuntimeException(
                "Could not parse {$path}. Values containing # ; = or quotes must be wrapped in double quotes."
            );
        }

        self::$values = $parsed;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$values === null) {
            throw new RuntimeException('Config::load() must be called before Config::get().');
        }

        return self::$values[$key] ?? $default;
    }

    public static function mustGet(string $key): mixed
    {
        $value = self::get($key);

        if ($value === null) {
            throw new RuntimeException("Missing required configuration key: {$key}");
        }

        return $value;
    }

    public static function isDebug(): bool
    {
        return (bool) self::get('APP_DEBUG', false);
    }
}
