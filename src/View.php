<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Renders PHP templates from views/.
 *
 * The old views were PHP files that did `return "<body> ... </body>";` — one
 * long double-quoted string with every inner quote backslash-escaped. That
 * shape makes escaping interpolated data awkward, which is how unescaped output
 * ends up in a template. Templates are now ordinary PHP with a short e() helper.
 */
final class View
{
    public function __construct(private readonly string $directory)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $file = $this->directory . '/' . $template . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renderInLayout(string $template, string $title, array $data = []): string
    {
        return $this->render('layout', [
            'title' => $title,
            'content' => $this->render($template, $data),
        ]);
    }

    /**
     * Escape for HTML text and attribute context.
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}