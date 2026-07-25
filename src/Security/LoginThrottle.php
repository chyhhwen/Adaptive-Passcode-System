<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\BlocklistRepository;
use PDO;

/**
 * Rate limits authentication attempts per IP address.
 *
 * This is the piece the project was missing: the `cache` table (ip / fre / time)
 * was designed to hold these counters but nothing ever read or wrote it, and the
 * `list` blocklist could only be filled in by hand. Login therefore had no
 * attempt limit at all.
 *
 * Two tiers:
 *   - After maxAttempts attempts inside decaySeconds, the IP is locked out
 *     until the window expires.
 *   - If attempts keep accumulating up to blockThreshold, the IP is added to
 *     the permanent `list` blocklist.
 *
 * Counting keys on REMOTE_ADDR (see App\Request::clientIp), so it cannot be
 * reset by sending a forged X-Forwarded-For header.
 *
 * Concurrency: hit() increments first and decides afterwards, in a single
 * atomic statement. An earlier version checked the counter and incremented it
 * in two steps, which let concurrent requests all read "not yet locked out"
 * before any of them had incremented — measured at 7-9 guesses getting through
 * a limit of 5. Reading the counter back after the increment is safe: a
 * concurrent increment can only make the value we read larger, never smaller,
 * so the decision can only become stricter.
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
     * Records one attempt and reports what should happen to it.
     */
    public function hit(string $ip): ThrottleResult
    {
        $now = date('Y-m-d H:i:s');
        $cutoff = date('Y-m-d H:i:s', time() - $this->decaySeconds);

        // One statement does insert-or-increment, and also restarts the window
        // when the stored one has expired.
        //
        // MySQL evaluates the assignments left to right, so `fre` still sees the
        // previous `time` value when it decides between restarting at 1 and
        // incrementing. The `time` assignment must therefore come second.
        $statement = $this->pdo->prepare(
            'INSERT INTO `cache` (`ip`, `fre`, `time`) VALUES (:ip, 1, :now)
             ON DUPLICATE KEY UPDATE
                `fre`  = IF(`time` < :cutoff_a, 1, `fre` + 1),
                `time` = IF(`time` < :cutoff_b, :now_b, `time`)'
        );

        // Placeholders are not reused: with emulation disabled MySQL binds each
        // marker exactly once.
        $statement->execute([
            'ip' => $ip,
            'now' => $now,
            'cutoff_a' => $cutoff,
            'cutoff_b' => $cutoff,
            'now_b' => $now,
        ]);

        $window = $this->currentWindow($ip);

        $attempts = $window['fre'] ?? 1;
        $startedAt = $window['started'] ?? time();

        $justBlocklisted = false;

        if ($attempts >= $this->blockThreshold && !$this->blocklist->isBlocked($ip)) {
            $this->blocklist->block($ip);
            $justBlocklisted = true;
        }

        $lockedOut = $attempts > $this->maxAttempts;

        return new ThrottleResult(
            attempts: $attempts,
            lockedOut: $lockedOut,
            justBlocklisted: $justBlocklisted,
            secondsUntilRetry: $lockedOut
                ? max(0, $this->decaySeconds - (time() - $startedAt))
                : 0,
        );
    }

    /**
     * Clears the counter after a successful authentication, so an honest user
     * who mistyped a few times is not penalised afterwards.
     */
    public function clear(string $ip): void
    {
        $this->pdo
            ->prepare('DELETE FROM `cache` WHERE `ip` = ?')
            ->execute([$ip]);

        // A successful sign-in is infrequent enough to be a good moment to tidy
        // up counters left behind by other addresses.
        $this->purgeExpired();
    }

    /**
     * Drops counters whose window has already expired, so the table stays
     * bounded without needing a scheduled job.
     */
    public function purgeExpired(): void
    {
        $this->pdo
            ->prepare('DELETE FROM `cache` WHERE `time` < ?')
            ->execute([date('Y-m-d H:i:s', time() - $this->decaySeconds)]);
    }

    /**
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

        if ($started === false) {
            return null;
        }

        return ['fre' => (int) $row['fre'], 'started' => $started];
    }
}
