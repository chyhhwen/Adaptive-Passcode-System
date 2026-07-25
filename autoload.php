<?php

declare(strict_types=1);

/**
 * Class loading entry point.
 *
 * Composer is not installed in this environment yet, so this file registers a
 * PSR-4 loader that follows exactly the same mapping as composer.json
 * ("App\" => "src/"). Once `composer install` has been run, vendor/autoload.php
 * takes over automatically and this fallback is skipped — no code changes needed.
 */

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';

    return;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
