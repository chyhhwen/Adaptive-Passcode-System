<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\BlocklistRepository;
use PDO;

/**
 * Rate limits failed login attempts per IP address.
 *
 * This is the piece the project was missing: the `cache` table (ip / fre / time)
 * was designed to hold these counters but nothing ever read or wrote it, and the
 * `list` blocklist could only be filled in by hand. Login therefore had no
 * attempt limit at all.
 *
 * Two tiers:
 *   - After maxAttempts failures inside decaySeconds, the IP is locked out
 *     until the window expires.
 *   - If failures keep accumulating up to blockThreshold, the IP is added to
 *     the permanent `list` blocklist.
 *
 * Counting is keyed on REMOTE_ADDR (see App\Request::clientIp), so it cannot be
 * reset by sending a forged X-Forwarded-For header.
 */
final class LoginThrottle
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly BlocklistRepository $blocklist,
        private readonly int $maxAttempts = 5,
        private readonly int $decaySeconds = 900,
        private readonly int $blockThreshold = 25,
    ) {
    }

    /**
     * True when this IP has spent its attempts and must wait.
     */
    public function isLockedOut(string $ip): bool
    {
        $record = $this->currentWindow($ip);

        return $record !== null && $record['fre'] >= $this->maxAttempts;
    }

    /**
     * Seconds remaining before the window resets. 0 when not locked out.
     */
    public function secondsUntilRetry(string $ip): int
    {
        $record = $this->currentWindow($ip);

        if ($record === null || $record['fre'] < $this->maxAttempts) {
            return 0;
        }

        $elapsed = time() - $record['started'];

        return max(0, $this->decaySeconds - $elapsed);
    }

    /**
     * Records one failed attempt. Returns true if this failure pushed the IP
     * onto the permanent blocklist.
     */
    public function recordFailure(string $ip): bool
    {
        $record = $this->currentWindow($ip);

        if ($record === null) {
            // Opening a new window is an infrequent, natural moment to drop
            // counters that have aged out, so the table stays bounded without
            // needing a scheduled job.
            $this->purgeExpired();

            // No row, or the previous window has expired: start a fresh one.
            $this->pdo
                ->prepare('DELETE FROM `cache` WHERE `ip` = ?')
                ->execute([$ip]);

            $this->pdo
                ->prepare('INSERT INTO `cache` (`ip`, `fre`, `time`) VALUES (?, 1, ?)')
                ->execute([$ip, date('Y-m-d H:i:s')]);

            return false;
        }

        $failures = $record['fre'] + 1;

        $this->pdo
            ->prepare('UPDATE `cache` SET `fre` = ? WHERE `ip` = ?')
            ->execute([$failures, $ip]);

        if ($failures >= $this->blockThreshold && !$this->blocklist->isBlocked($ip)) {
            $this->blocklist->block($ip);

            return true;
        }

        return false;
    }

    /**
     * Clears the counter after a successful login, so an honest user who
     * mistyped a few times is not penalised afterwards.
     */
    public function clear(string $ip): void
    {
        $this->pdo
            ->prepare('DELETE FROM `cache` WHERE `ip` = ?')
            ->execute([$ip]);
    }

    /**
     * Drops counters whose window has already expired. Called opportunistically
     * so the table does not grow without bound.
     */
    public function purgeExpired(): void
    {
        $this->pdo
            ->prepare('DELETE FROM `cache` WHERE `time` < ?')
            ->execute([date('Y-m-d H:i:s', time() - $this->decaySeconds)]);
    }

    /**
     * The active counter row for this IP, or null when there is none or the
     * window has already expired.
     *
     * @return array{fre: int, started: int}|null
     */
    private function currentWindow(string $ip): ?array
    {
        $statement = $this->pdo->prepare('SELECT `fre`, `time` FROM `cache` WHERE `ip` = ? LIMIT 1');
        $statement->execute([$ip]);

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        $started = strtotime((string) $row['time']);

        if ($started === false || (time() - $started) >= $this->decaySeconds) {
            return null;
        }

        return ['fre' => (int) $row['fre'], 'started' => $started];
    }
}