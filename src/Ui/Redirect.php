<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * Brings the user back to the page a form was sent from, in wp-admin or on the site.
 *
 * Forms built with {@see Form} carry the page's URL in the field `redirect_to`.
 */
final class Redirect
{
    public const FIELD = 'redirect_to';

    /**
     * The URL of the current request.
     */
    public static function currentUrl(): string
    {
        $host = \is_string($_SERVER['HTTP_HOST'] ?? null) ? $_SERVER['HTTP_HOST'] : '';
        $uri = \is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
        if ($host === '') {
            return \home_url($uri);
        }

        return \set_url_scheme('//' . $host . $uri);
    }

    /**
     * The URL in `redirect_to` if it points to this site, else the referer or the fallback.
     */
    public static function target(?string $fallback = null): string
    {
        $fallback ??= (string) (\wp_get_referer() ?: \home_url('/'));
        $requested = $_REQUEST[self::FIELD] ?? '';

        return \is_string($requested) && $requested !== ''
            ? \wp_validate_redirect(\wp_unslash($requested), $fallback)
            : $fallback;
    }

    /**
     * Redirects to {@see target()} with the given query arguments and ends the request.
     *
     * @param array<string, string|int> $args
     */
    public static function back(array $args = [], ?string $fallback = null): never
    {
        $url = self::target($fallback);
        if ($args !== []) {
            $url = \add_query_arg(\array_map('rawurlencode', \array_map('strval', $args)), $url);
        }

        \wp_safe_redirect($url);
        exit;
    }
}
