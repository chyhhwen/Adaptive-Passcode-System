<?php

declare(strict_types=1);

namespace App;

/**
 * Read-only accessors for the incoming request.
 */
final class Request
{
    /**
     * The connecting IP address.
     *
     * Only REMOTE_ADDR is used. HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR are
     * ordinary request headers that any client can set to any value, so an
     * IP-based blocklist that trusts them can be bypassed by sending one extra
     * header. REMOTE_ADDR is filled in by the web server from the TCP peer.
     *
     * If this app is ever put behind a reverse proxy, the correct fix is to
     * trust X-Forwarded-For ONLY when REMOTE_ADDR is the known proxy address —
     * not to read the header unconditionally.
     */
    public static function clientIp(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return $ip === '::1' ? '127.0.0.1' : $ip;
    }

    /**
     * The request path, without query string.
     *
     * Replaces str_split($uri, 4) / str_split($uri, 5), which chopped the URI
     * into fixed-width chunks and broke whenever a path length changed.
     */
    public static function path(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return '/';
        }

        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public static function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function post(string $key, ?string $default = null): ?string
    {
        $value = $_POST[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * Decoded JSON request body, or an empty array when the body is absent or
     * malformed.
     */
    public static function json(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}