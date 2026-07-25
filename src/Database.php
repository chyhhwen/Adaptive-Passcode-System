<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * The single source of database connections.
 *
 * Replaces both the old sql::conn() (which hardcoded host=localhost and had no
 * way to configure it) and the duplicate conn() copy-pasted into api/, which
 * required a config.php that was never in the repository.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            (string) Config::get('DB_HOST', '127.0.0.1'),
            (int) Config::get('DB_PORT', 3306),
            (string) Config::mustGet('DB_DATABASE')
        );

        self::$pdo = new PDO(
            $dsn,
            (string) Config::get('DB_USERNAME', ''),
            (string) Config::get('DB_PASSWORD', ''),
            [
                // Surface failures instead of returning false and continuing.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Use real server-side prepared statements. With emulation on,
                // PDO interpolates values into the SQL string itself, which is
                // weaker and depends on the connection charset being correct.
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$pdo;
    }
}
