<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

/**
 * The IP blocklist backing the `list` table.
 *
 * Entries are added automatically by App\Security\LoginThrottle once an address
 * has failed enough login attempts; they can also still be added by hand.
 */
final class BlocklistRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function isBlocked(string $ip): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM `list` WHERE `ip` = ? LIMIT 1');
        $statement->execute([$ip]);

        return $statement->fetchColumn() !== false;
    }

    public function block(string $ip): void
    {
        if ($this->isBlocked($ip)) {
            return;
        }

        $statement = $this->pdo->prepare('INSERT INTO `list` (`ip`, `time`) VALUES (?, ?)');
        $statement->execute([$ip, date('Y-m-d H:i:s')]);
    }
}