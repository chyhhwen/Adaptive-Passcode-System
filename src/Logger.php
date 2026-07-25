<?php

declare(strict_types=1);

namespace App;

/**
 * Appends messages to a daily log file.
 *
 * The old txt class read its filename from $GLOBALS['time'], which coupled it
 * to whichever script happened to have defined a global $time. On the
 * defense/ code path that variable was never set, which would have produced a
 * file literally named ".txt". The directory is now injected instead.
 */
final class Logger
{
    public function __construct(private readonly string $directory)
    {
    }

    public function write(string $message): bool
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0o775, true) && !is_dir($this->directory)) {
            return false;
        }

        $file = $this->directory . '/' . date('Y-m-d') . '.log';

        $line = sprintf(
            '[%s] %s %s%s',
            date('Y-m-d H:i:s'),
            Request::clientIp(),
            str_replace(["\r", "\n"], ' ', $message),
            PHP_EOL
        );

        // LOCK_EX prevents concurrent requests interleaving partial lines.
        return file_put_contents($file, $line, FILE_APPEND | LOCK_EX) !== false;
    }
}