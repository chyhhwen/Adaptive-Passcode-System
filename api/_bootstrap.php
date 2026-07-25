<?php

declare(strict_types=1);

/**
 * Shared bootstrap for the JSON endpoints in this directory.
 *
 * These files are served directly by Apache (the .htaccess rewrite skips real
 * files), so they never pass through the front controller and must set
 * themselves up.
 */

require dirname(__DIR__) . '/autoload.php';

use App\Config;
use App\Csrf;
use App\Request;
use App\Session;

date_default_timezone_set('Asia/Taipei');

Config::load(dirname(__DIR__) . '/.env');

error_reporting(E_ALL);
ini_set('display_errors', Config::isDebug() ? '1' : '0');
ini_set('log_errors', '1');

Session::start();

header('Content-Type: application/json; charset=utf-8');

// No Access-Control-Allow-Origin header at all.
//
// These endpoints previously sent "Access-Control-Allow-Origin: *", which tells
// the browser that any website may read the response. Combined with a cookie
// session that is exactly how another origin reads or modifies a logged-in
// user's data. Omitting the header restores the default same-origin policy.

/**
 * Sends a JSON response and stops.
 */
function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Rejects unauthenticated callers.
 */
function require_authentication(): void
{
    if (!Session::isAuthenticated()) {
        json_response(['ok' => false, 'message' => 'Authentication required'], 401);
    }
}

/**
 * Rejects requests using the wrong HTTP verb.
 */
function require_method(string $method): void
{
    if (Request::method() !== strtoupper($method)) {
        json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
    }
}

/**
 * Rejects state-changing requests without a valid CSRF token.
 */
function require_csrf(): void
{
    $token = $_POST[Csrf::FIELD] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    // 403, not 419: 419 is not a registered status code and Apache turns
    // unknown codes into 500.
    if (!Csrf::isValid($token)) {
        json_response(['ok' => false, 'message' => 'CSRF token mismatch'], 403);
    }
}