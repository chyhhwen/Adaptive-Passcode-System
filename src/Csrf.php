<?php

declare(strict_types=1);

namespace App;

/**
 * Cross-Site Request Forgery tokens.
 *
 * Without these, any external page could submit the login, register or delete
 * forms on a visitor's behalf using their existing cookie.
 */
final class Csrf
{
    public const FIELD = '_csrf';

    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    /**
     * Ready-to-print hidden input for HTML forms.
     */
    public static function field(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::FIELD,
            htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
        );
    }

    public static function isValid(mixed $candidate): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? '';

        if (!is_string($candidate) || $expected === '') {
            return false;
        }

        // hash_equals compares in constant time, so a caller cannot learn the
        // token byte by byte from response timing.
        return hash_equals($expected, $candidate);
    }

    /**
     * Rejects the request outright when the token does not match.
     *
     * Uses 403. The 419 code some frameworks return for this is not a
     * registered HTTP status, and Apache replaces unknown codes with 500 —
     * which would report a client-side token problem as a server fault.
     */
    public static function verifyOrFail(mixed $candidate): void
    {
        if (!self::isValid($candidate)) {
            http_response_code(403);
            exit('CSRF token mismatch.');
        }
    }
}