<?php

declare(strict_types=1);

namespace App;

/**
 * A small method + path routing table.
 *
 * The previous router decided where to send a request by slicing the URI into
 * fixed-width chunks with str_split($uri, 4) and reassembling them, so any
 * change in path length silently changed the route.
 */
final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    /** @var callable|null */
    private $fallback = null;

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * Registers a handler for both GET and POST — used by pages that render a
     * form and also receive its submission.
     */
    public function any(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
        $this->add('POST', $path, $handler);
    }

    public function fallback(callable $handler): void
    {
        $this->fallback = $handler;
    }

    public function dispatch(string $method, string $path): string
    {
        $handler = $this->routes[strtoupper($method)][$path] ?? null;

        if ($handler === null) {
            // A path that exists but not for this verb is a 405, not a 404.
            $knownPath = false;

            foreach ($this->routes as $byPath) {
                if (isset($byPath[$path])) {
                    $knownPath = true;
                    break;
                }
            }

            http_response_code($knownPath ? 405 : 404);

            return $this->fallback === null ? '' : (string) ($this->fallback)();
        }

        return (string) $handler();
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }
}