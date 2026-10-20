<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * Sends visitors who open a protected link, e.g. a sheet music download, to the page with
 * `[orchestra_login]` instead of WordPress's login screen, and back to the link after logging in.
 *
 * Only links to the site's pages and to `admin-post.php` (the actions of the Orchestra plugins)
 * are changed. wp-admin, and logins that WordPress forces again (`reauth`), keep `wp-login.php`.
 */
final class LoginPageRedirect
{
    public static function register(): void
    {
        \add_filter('login_url', [self::class, 'filterLoginUrl'], 10, 3);
    }

    /**
     * @internal Hooked to `login_url`.
     */
    public static function filterLoginUrl(string $loginUrl, string $redirect, bool $forceReauth): string
    {
        $page = Pages::url(Pages::LOGIN);

        return $page === null
            ? $loginUrl
            : (self::orchestraLoginUrl($page, $redirect, $forceReauth, \admin_url()) ?? $loginUrl);
    }

    /**
     * The URL of the Orchestra login page for a login that should return to `$redirect`, or null if
     * WordPress's login screen should be used.
     */
    public static function orchestraLoginUrl(
        string $loginPage,
        string $redirect,
        bool $forceReauth,
        string $adminUrl,
    ): ?string {
        if ($forceReauth || ($redirect !== '' && !self::outsideAdmin($redirect, $adminUrl))) {
            return null;
        }

        return $redirect === ''
            ? $loginPage
            : $loginPage . (\str_contains($loginPage, '?') ? '&' : '?') . 'redirect_to=' . \rawurlencode($redirect);
    }

    /**
     * Whether a URL is a page of the site or an action of `admin-post.php`, not a wp-admin screen.
     */
    public static function outsideAdmin(string $url, string $adminUrl): bool
    {
        return !\str_starts_with($url, $adminUrl) || \str_starts_with($url, $adminUrl . 'admin-post.php');
    }
}
