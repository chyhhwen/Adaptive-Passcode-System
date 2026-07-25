<?php

declare(strict_types=1);

/**
 * Front controller.
 *
 * Every request that is not a real file on disk is rewritten here by .htaccess.
 */

require __DIR__ . '/autoload.php';

use App\Config;
use App\Csrf;
use App\Database;
use App\Logger;
use App\Repository\BlocklistRepository;
use App\Repository\MemberRepository;
use App\Repository\PictureRepository;
use App\Request;
use App\Router;
use App\Security\LoginThrottle;
use App\Session;
use App\View;

date_default_timezone_set('Asia/Taipei');

Config::load(__DIR__ . '/.env');

// APP_DEBUG now actually does something. Errors are always logged; they are only
// shown in the browser when debugging, so a stack trace cannot leak the database
// credentials and filesystem paths to a visitor.
error_reporting(E_ALL);
ini_set('display_errors', Config::isDebug() ? '1' : '0');
ini_set('log_errors', '1');

Session::start();

$view = new View(__DIR__ . '/views');
$logger = new Logger(__DIR__ . '/log');

$renderError = static function (int $status, string $message) use ($view): string {
    http_response_code($status);

    return $view->renderInLayout('error', $status . ' ' . $message, [
        'status' => $status,
        'message' => $message,
    ]);
};

try {
    $pdo = Database::connection();
} catch (Throwable $e) {
    $logger->write('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit($renderError(503, 'SERVICE UNAVAILABLE'));
}

$members = new MemberRepository($pdo);
$blocklist = new BlocklistRepository($pdo);
$pictures = new PictureRepository($pdo);
$throttle = new LoginThrottle($pdo, $blocklist);

$clientIp = Request::clientIp();

// Blocklist gate. This log call used to sit after a die() and so never ran.
if ($blocklist->isBlocked($clientIp)) {
    $logger->write('Blocked IP attempted access: ' . Request::path());
    exit($renderError(404, 'NOT FOUND'));
}

$router = new Router();

$router->get('/', static function () use ($view, $members): string {
    if (!Session::isAuthenticated()) {
        return $view->renderInLayout('login', 'ChiXiao', ['error' => null]);
    }

    return $view->renderInLayout('home', 'ChiXiao', [
        'view' => $view,
        'userName' => Session::userName(),
    ]);
});

$router->post('/login', static function () use ($view, $members, $logger, $throttle, $clientIp): string {
    Csrf::verifyOrFail(Request::post(Csrf::FIELD));

    // The attempt is recorded before the password is looked at, and the counter
    // it returns is what decides the outcome. Checking first and incrementing
    // afterwards would let concurrent requests all pass the check before any of
    // them had counted.
    $attempt = $throttle->hit($clientIp);

    if ($attempt->justBlocklisted) {
        $logger->write('Blocklisted after repeated attempts');
    }

    if ($attempt->lockedOut) {
        $minutes = (int) ceil($attempt->secondsUntilRetry / 60);
        $logger->write('Throttled login attempt');
        http_response_code(429);

        return $view->renderInLayout('login', 'ChiXiao', [
            'error' => "嘗試次數過多，請於 {$minutes} 分鐘後再試",
        ]);
    }

    $username = trim((string) Request::post('user', ''));
    $password = (string) Request::post('pass', '');

    $member = $members->verifyCredentials($username, $password);

    if ($member === null) {
        $logger->write('Failed login for user: ' . $username);

        http_response_code(401);

        // Deliberately does not say whether it was the username or the password
        // that was wrong.
        return $view->renderInLayout('login', 'ChiXiao', ['error' => '帳號或密碼錯誤']);
    }

    // A genuine user who mistyped a few times should not stay penalised.
    $throttle->clear($clientIp);

    Session::login((int) $member['id'], (string) $member['name']);

    header('Location: /');
    exit;
});

$router->post('/register', static function () use ($view, $members, $logger, $throttle, $clientIp): string {
    Csrf::verifyOrFail(Request::post(Csrf::FIELD));

    // Registration is rate limited on the same per-IP counter as login.
    // Without this, accounts could be created in unlimited numbers.
    //
    // The counter is deliberately NOT cleared on success: a script creating one
    // account after another must keep accumulating until it is locked out, and
    // eventually blocklisted.
    $attempt = $throttle->hit($clientIp);

    if ($attempt->justBlocklisted) {
        $logger->write('Blocklisted after repeated attempts');
    }

    if ($attempt->lockedOut) {
        $minutes = (int) ceil($attempt->secondsUntilRetry / 60);
        $logger->write('Throttled registration attempt');
        http_response_code(429);

        return $view->renderInLayout('login', 'ChiXiao', [
            'error' => "嘗試次數過多，請於 {$minutes} 分鐘後再試",
        ]);
    }

    $name = trim((string) Request::post('name1', ''));
    $username = trim((string) Request::post('user1', ''));
    $password = (string) Request::post('pass1', '');

    // Server-side validation. The checks in public/index.js run in the browser
    // and are only there to give quick feedback — anyone can bypass them.
    $error = match (true) {
        $name === '' || $username === '' || $password === '' => '請填寫所有欄位',
        mb_strlen($name) > 32 || mb_strlen($username) > 32 => '名稱或帳號過長',
        mb_strlen($password) < 8 => '密碼至少需要 8 個字元',
        $members->usernameExists($username) => '此帳號已被使用',
        default => null,
    };

    if ($error !== null) {
        http_response_code(422);

        return $view->renderInLayout('login', 'ChiXiao', ['error' => $error]);
    }

    // Registration writes through the repository directly. The old code issued
    // an HTTP request from the app to its own /api/menber_add.php, which is why
    // that endpoint had to stay unauthenticated.
    if (!$members->create($name, $username, $password)) {
        $logger->write('Registration failed for user: ' . $username);
        http_response_code(500);

        return $view->renderInLayout('login', 'ChiXiao', ['error' => '註冊失敗，請稍後再試']);
    }

    header('Location: /');
    exit;
});

$router->post('/logout', static function (): string {
    Csrf::verifyOrFail(Request::post(Csrf::FIELD));

    Session::logout();

    header('Location: /');
    exit;
});

$router->get('/about', static fn (): string => $view->renderInLayout('about', 'About', ['view' => $view]));

$router->get('/photo', static function () use ($view, $pictures): string {
    return $view->renderInLayout(
        'photo',
        'Photo',
        [
            'view' => $view,
            'pictures' => $pictures->all(),
        ],
        // Gallery grid rules, only needed on this page.
        ['/public/photo.css']
    );
});

// The forms live on the home page, so a bare GET of a form action — a bookmark
// or a refresh after submitting — sends the visitor there rather than to a 405.
$formRedirect = static function (): string {
    header('Location: /');
    exit;
};

$router->get('/login', $formRedirect);
$router->get('/register', $formRedirect);

$router->get('/youjia', static function () use ($renderError): string {
    $file = __DIR__ . '/youjia.jpg';

    if (!is_file($file)) {
        return $renderError(404, 'NOT FOUND');
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/jpeg');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: must-revalidate');

    readfile($file);
    exit;
});

$router->fallback(static fn (): string => $renderError(http_response_code() ?: 404, 'NOT FOUND'));

// Single place where unexpected failures are turned into a response. Details go
// to the log; the visitor only sees a generic page, so an exception message can
// never expose credentials, SQL or filesystem paths.
try {
    echo $router->dispatch(Request::method(), Request::path());
} catch (Throwable $e) {
    $logger->write(sprintf(
        '%s: %s in %s:%d',
        $e::class,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    echo $renderError(500, 'INTERNAL SERVER ERROR');
}