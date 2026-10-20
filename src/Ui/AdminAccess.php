<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

use Orchestra\Core\Authorization\Roles;
use WP_User;

/**
 * Keeps members, conductors and organizers out of wp-admin: everything they need is on the
 * Orchestra pages. Administrators and users with other WordPress roles are not affected.
 */
final class AdminAccess
{
    /** Filters whether a user is kept out of wp-admin: `(bool $restricted, WP_User $user)`. */
    public const FILTER = 'orchestra_core_restrict_admin';

    public static function register(): void
    {
        \add_action('admin_init', [self::class, 'redirectFromAdmin'], 1);
        // Screens the user may not open are rejected before admin_init.
        \add_action('admin_page_access_denied', [self::class, 'redirectFromAdmin']);
        \add_filter('show_admin_bar', [self::class, 'showAdminBar']);
        \add_filter('login_redirect', [self::class, 'loginRedirect'], 10, 3);
    }

    /**
     * Users whose roles are all Orchestra roles (and possibly subscriber).
     */
    public static function isRestricted(WP_User $user): bool
    {
        $roles = \array_values($user->roles);
        if (\array_intersect($roles, Roles::GRANTABLE) === []) {
            return false;
        }
        if (\array_diff($roles, [...Roles::GRANTABLE, 'subscriber']) !== []) {
            return false;
        }

        return \apply_filters(self::FILTER, true, $user) === true;
    }

    /**
     * Where restricted users go instead of wp-admin.
     */
    public static function homeUrl(): string
    {
        return Pages::url(Pages::PORTAL) ?? \home_url('/');
    }

    /**
     * @internal Hooked to `admin_init` and `admin_page_access_denied`.
     */
    public static function redirectFromAdmin(): void
    {
        global $pagenow;
        $user = \wp_get_current_user();
        if (
            \wp_doing_ajax()
            || \wp_doing_cron()
            || \in_array($pagenow, ['admin-post.php', 'admin-ajax.php', 'async-upload.php'], true)
            || !$user->exists()
            || !self::isRestricted($user)
        ) {
            return;
        }

        \wp_safe_redirect(self::homeUrl());
        exit;
    }

    /**
     * @internal Hooked to `show_admin_bar`.
     */
    public static function showAdminBar(bool $show): bool
    {
        $user = \wp_get_current_user();

        return $show && !($user->exists() && self::isRestricted($user));
    }

    /**
     * Sends restricted users to the Orchestra pages after logging in, unless they asked for a page
     * outside wp-admin or an action of `admin-post.php`, e.g. a download.
     *
     * @internal Hooked to `login_redirect`.
     */
    public static function loginRedirect(string $redirectTo, string $requested, mixed $user): string
    {
        if (!$user instanceof WP_User || !self::isRestricted($user)) {
            return $redirectTo;
        }
        if ($requested !== '' && LoginPageRedirect::outsideAdmin($requested, \admin_url())) {
            return $redirectTo;
        }

        return self::homeUrl();
    }
}
