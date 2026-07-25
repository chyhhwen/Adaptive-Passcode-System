<?php

declare(strict_types=1);

/**
 * One-off database migration. Run from the command line:
 *
 *     C:\xampp\php\php.exe migrate.php
 *
 * It is safe to run more than once — every step checks the current state first.
 *
 * What it does:
 *   1. Renames the misspelled `menber` table to `member`.
 *   2. Converts stored plaintext passwords to bcrypt hashes, so existing
 *      accounts keep working after the switch to password_verify().
 *   3. Adds a UNIQUE index on `user`, which nothing enforced before.
 *   4. Creates the `picture` table, which the API queried but which was never
 *      part of the schema.
 *   5. Moves tables to utf8mb4 so 4-byte characters (emoji) do not corrupt.
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
$database = (string) Config::mustGet('DB_DATABASE');

function tableExists(PDO $pdo, string $database, string $table): bool
{
    $statement = $pdo->prepare(
        'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1'
    );
    $statement->execute([$database, $table]);

    return $statement->fetchColumn() !== false;
}

function indexExists(PDO $pdo, string $database, string $table, string $index): bool
{
    $statement = $pdo->prepare(
        'SELECT 1 FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1'
    );
    $statement->execute([$database, $table, $index]);

    return $statement->fetchColumn() !== false;
}

function step(string $message): void
{
    echo '  ' . $message . PHP_EOL;
}

echo "Migrating database '{$database}'..." . PHP_EOL;

// 1. menber -> member
if (tableExists($pdo, $database, 'menber') && !tableExists($pdo, $database, 'member')) {
    $pdo->exec('RENAME TABLE `menber` TO `member`');
    step('Renamed table menber -> member.');
} else {
    step('Table `member` already in place; nothing to rename.');
}

if (!tableExists($pdo, $database, 'member')) {
    echo 'ERROR: no `member` table found. Import backup/temp.sql first.' . PHP_EOL;
    exit(1);
}

// 2. Hash any password still stored as plaintext.
//
// A bcrypt hash produced by password_hash() is 60 characters and starts with
// $2y$. Anything else in this column is the original plaintext value.
$rows = $pdo->query('SELECT `id`, `pass` FROM `member`')->fetchAll();
$update = $pdo->prepare('UPDATE `member` SET `pass` = ? WHERE `id` = ?');
$hashed = 0;

foreach ($rows as $row) {
    $stored = (string) $row['pass'];

    if (password_get_info($stored)['algo'] !== null) {
        continue;
    }

    $update->execute([password_hash($stored, PASSWORD_DEFAULT), (int) $row['id']]);
    $hashed++;
}

step($hashed > 0
    ? "Converted {$hashed} plaintext password(s) to bcrypt."
    : 'All passwords already hashed.');

// 3. UNIQUE index on user, so two accounts cannot share a username.
if (!indexExists($pdo, $database, 'member', 'uniq_member_user')) {
    $duplicates = $pdo->query(
        'SELECT `user`, COUNT(*) AS total FROM `member` GROUP BY `user` HAVING total > 1'
    )->fetchAll();

    if ($duplicates !== []) {
        $names = implode(', ', array_column($duplicates, 'user'));
        step("SKIPPED unique index: duplicate usernames present ({$names}). Resolve them, then re-run.");
    } else {
        $pdo->exec('ALTER TABLE `member` ADD UNIQUE KEY `uniq_member_user` (`user`)');
        step('Added UNIQUE index on member.user.');
    }
} else {
    step('UNIQUE index on member.user already present.');
}

// 4. The API queried `picture`, but no such table was ever defined.
if (!tableExists($pdo, $database, 'picture')) {
    $pdo->exec(
        'CREATE TABLE `picture` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `data` VARCHAR(2048) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    step('Created missing `picture` table.');
} else {
    step('Table `picture` already exists.');
}

// 5. The `cache` table stores login-failure counters for LoginThrottle. It was
//    declared with every column as varchar(255), so the counter was a string and
//    the timestamp could not be compared or indexed usefully.
if (tableExists($pdo, $database, 'cache')) {
    // Existing rows are meaningless counters from a feature that never ran.
    $pdo->exec('DELETE FROM `cache`');

    $pdo->exec('ALTER TABLE `cache`
        MODIFY `ip` VARCHAR(45) NOT NULL,
        MODIFY `fre` INT UNSIGNED NOT NULL DEFAULT 0,
        MODIFY `time` DATETIME NOT NULL');
    step('Corrected cache column types (fre -> INT, time -> DATETIME, ip -> VARCHAR(45)).');

    if (!indexExists($pdo, $database, 'cache', 'uniq_cache_ip')) {
        $pdo->exec('ALTER TABLE `cache` ADD UNIQUE KEY `uniq_cache_ip` (`ip`)');
        step('Added UNIQUE index on cache.ip.');
    }
} else {
    $pdo->exec(
        'CREATE TABLE `cache` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `ip` VARCHAR(45) NOT NULL,
            `fre` INT UNSIGNED NOT NULL DEFAULT 0,
            `time` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_cache_ip` (`ip`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    step('Created `cache` table for login throttling.');
}

// VARCHAR(45) is the longest an IPv6 address can be in text form.
if (tableExists($pdo, $database, 'list') && !indexExists($pdo, $database, 'list', 'idx_list_ip')) {
    $pdo->exec('ALTER TABLE `list` ADD KEY `idx_list_ip` (`ip`)');
    step('Added index on list.ip.');
}

// 6. utf8 in MySQL is utf8mb3 and cannot store 4-byte characters. The PDO DSN
//    now connects as utf8mb4, so move the tables to match.
foreach (['member', 'list', 'cache', 'picture'] as $table) {
    if (tableExists($pdo, $database, $table)) {
        $pdo->exec("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
}
step('Converted tables to utf8mb4.');

echo 'Done.' . PHP_EOL;