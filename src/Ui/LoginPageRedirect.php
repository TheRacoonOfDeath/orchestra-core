<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * Sends visitors who open a protected link, e.g. a sheet music download, to the page with
 * `[orchestra_login]` (or, without one, the start page with its login form) instead of WordPress's
 * login screen, and back to the link after logging in.
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
        $page = Pages::url(Pages::LOGIN) ?? Pages::url(Pages::PORTAL);

        return $page === null
            ? $loginUrl
            : (self::orchestraLoginUrl($page, $redirect, $forceReauth, \admin_url()) ?? $loginUrl);
    }

    /**
     * Sends a logged-out user from an action of `admin-post.php` to the login, and back afterwards:
     * to the link itself, or for a form to the page it was sent from. Plugins hook it to
     * `admin_post_nopriv_<action>`. Unlike WordPress's `auth_redirect`, it does not force a new
     * login, so the Orchestra login page is used.
     */
    public static function requireLogin(): never
    {
        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $return = $isPost ? Redirect::target(AdminAccess::homeUrl()) : Redirect::currentUrl();

        \nocache_headers();
        \wp_safe_redirect(\wp_login_url($return));
        exit;
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
