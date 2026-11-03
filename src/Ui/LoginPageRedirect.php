<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * Sends visitors who open an Orchestra link that needs a login, e.g. a sheet music download, to the
 * page with `[orchestra_login]` (or, without one, the start page with its login form) instead of
 * WordPress's login screen, and back to the link after logging in.
 *
 * Only links to the Orchestra pages (pages with an Orchestra shortcode) and to the Orchestra actions
 * of `admin-post.php` (`orchestra_*`) are changed, so the Orchestra pages can be an area inside a
 * website with logins of its own. All other logins, wp-admin, and logins that WordPress forces
 * again (`reauth`) keep `wp-login.php`.
 */
final class LoginPageRedirect
{
    private const ACTION_PREFIX = 'orchestra_';

    public static function register(): void
    {
        \add_filter('login_url', [self::class, 'filterLoginUrl'], 10, 3);
    }

    /**
     * @internal Hooked to `login_url`.
     */
    public static function filterLoginUrl(string $loginUrl, string $redirect, bool $forceReauth): string
    {
        $page = self::loginPage();
        if ($page === null || !(self::isOrchestraAction($redirect, \admin_url()) || self::isOrchestraPage($redirect))) {
            return $loginUrl;
        }

        return self::orchestraLoginUrl($page, $redirect, $forceReauth) ?? $loginUrl;
    }

    /**
     * Sends a logged-out user from an action of `admin-post.php` to the login, and back afterwards:
     * to the link itself, or for a form to the page it was sent from. Plugins hook it to
     * `admin_post_nopriv_<action>`. Unlike WordPress's `auth_redirect`, it does not force a new
     * login, and it always uses the Orchestra login page (if there is one).
     */
    public static function requireLogin(): never
    {
        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $return = $isPost ? Redirect::target(AdminAccess::homeUrl()) : Redirect::currentUrl();
        $page = self::loginPage();

        \nocache_headers();
        \wp_safe_redirect(
            ($page === null ? null : self::orchestraLoginUrl($page, $return, false)) ?? \wp_login_url($return)
        );
        exit;
    }

    /**
     * The URL of the Orchestra login page for a login that should return to `$redirect`, or null if
     * WordPress's login screen should be used.
     */
    public static function orchestraLoginUrl(string $loginPage, string $redirect, bool $forceReauth): ?string
    {
        if ($forceReauth || $redirect === '') {
            return null;
        }

        return $loginPage . (\str_contains($loginPage, '?') ? '&' : '?') . 'redirect_to=' . \rawurlencode($redirect);
    }

    /**
     * Whether a URL is an Orchestra action of `admin-post.php`, e.g. a download.
     */
    public static function isOrchestraAction(string $url, string $adminUrl): bool
    {
        if (!\str_starts_with($url, $adminUrl . 'admin-post.php')) {
            return false;
        }
        \parse_str((string) \parse_url($url, PHP_URL_QUERY), $query);
        $action = $query['action'] ?? '';

        return \is_string($action) && \str_starts_with($action, self::ACTION_PREFIX);
    }

    /**
     * Whether a URL is a page of the site or an action of `admin-post.php`, not a wp-admin screen.
     */
    public static function outsideAdmin(string $url, string $adminUrl): bool
    {
        return !\str_starts_with($url, $adminUrl) || \str_starts_with($url, $adminUrl . 'admin-post.php');
    }

    private static function loginPage(): ?string
    {
        return Pages::url(Pages::LOGIN) ?? Pages::url(Pages::PORTAL);
    }

    private static function isOrchestraPage(string $url): bool
    {
        $postId = $url === '' ? 0 : \url_to_postid($url);
        $post = $postId > 0 ? \get_post($postId) : null;

        return $post instanceof \WP_Post
            && $post->post_status === 'publish'
            && InternalSite::isOrchestraContent($post->post_content);
    }
}
