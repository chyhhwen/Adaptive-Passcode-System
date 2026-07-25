<?php

declare(strict_types=1);

namespace App\Security;

/**
 * The outcome of recording one authentication attempt.
 */
final class ThrottleResult
{
    public function __construct(
        /** Attempts recorded in the current window, including this one. */
        public readonly int $attempts,
        /** True when the caller has spent its allowance and must be refused. */
        public readonly bool $lockedOut,
        /** True only on the attempt that pushed this address onto the blocklist. */
        public readonly bool $justBlocklisted,
        /** Seconds left before the window resets. */
        public readonly int $secondsUntilRetry,
    ) {
    }
}
