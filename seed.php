<?php

declare(strict_types=1);

/**
 * Populates the `picture` table from the files in public/images/.
 *
 * Run from the command line:
 *
 *     C:\xampp\php\php.exe seed.php
 *
 * Safe to run more than once: a file already referenced in the table is skipped.
 */

require __DIR__ . '/autoload.php';

use App\Config;
use App\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

Config::load(__DIR__ . '/.env');

$pdo = Database::connection();

$imageDirectory = __DIR__ . '/public/images';
$webPrefix = '/public/images/';

/**
 * index.jpg is the page background referenced by public/index.css, not a
 * gallery photo — seeding it would put the site's own backdrop in the album.
 */
$excluded = ['index.jpg'];

$files = glob($imageDirectory . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

if ($files === false || $files === []) {
    echo "No images found in {$imageDirectory}." . PHP_EOL;
    exit(1);
}

$names = array_map('basename', $files);
$names = array_values(array_diff($names, $excluded));

// "10.jpg" must sort after "9.jpg", which plain string comparison gets wrong.
natsort($names);
$names = array_values($names);

$existing = $pdo->prepare('SELECT 1 FROM `picture` WHERE `data` = ? LIMIT 1');
$insert = $pdo->prepare('INSERT INTO `picture` (`name`, `data`) VALUES (?, ?)');

$added = 0;
$skipped = 0;

foreach ($names as $name) {
    $path = $webPrefix . $name;

    $existing->execute([$path]);

    if ($existing->fetchColumn() !== false) {
        $skipped++;
        continue;
    }

    // Strip the extension for a slightly friendlier alt text.
    $insert->execute([pathinfo($name, PATHINFO_FILENAME), $path]);
    $added++;
}

echo sprintf(
    'Seeded %d picture(s), skipped %d already present. Excluded: %s%s',
    $added,
    $skipped,
    implode(', ', $excluded),
    PHP_EOL
);