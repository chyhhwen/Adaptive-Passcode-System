<?php

declare(strict_types=1);

namespace App;

/**
 * Session handling with the cookie flags the old implementation was missing.
 *
 * The previous call was:
 *     session_set_cookie_params(0, '/', 'localhost');
 * which set no httponly, no secure and no samesite, and pinned the cookie to
 * the literal host "localhost".
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            // Empty domain binds the cookie to the exact host that served the
            // response. Hardcoding 'localhost' meant the cookie was simply
            // dropped anywhere else.
            'domain' => '',
            // Only mark Secure over HTTPS; setting it on plain HTTP would make
            // the browser discard the cookie and break local development.
            'secure' => self::isHttps(),
            // Blocks JavaScript from reading the cookie, so an XSS bug cannot
            // be escalated straight into session theft.
            'httponly' => true,
            // Lax still allows normal top-level navigation but stops the cookie
            // riding along on cross-site POSTs.
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /**
     * Called on successful authentication.
     */
    public static function login(int $id, string $username): void
    {
        // Issue a brand new session ID so that an attacker who managed to fix a
        // known ID on the victim's browser does not end up holding an
        // authenticated session (session fixation).
        session_regenerate_id(true);

        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $username;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function userName(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    private static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }
}
