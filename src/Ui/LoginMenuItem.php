<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * A menu item that reads "Log in" for visitors and "Log out" for logged-in users, for the menus of
 * classic themes (e.g. Astra). Administrators add it under Appearance → Menus, box "Orchestra", or
 * as a custom link with the URL `#orchestra-login-logout`.
 *
 * "Log in" leads to the login page, "Log out" logs out and returns to the start page.
 */
final class LoginMenuItem
{
    public const URL = '#orchestra-login-logout';

    public static function register(): void
    {
        \add_filter('wp_nav_menu_objects', [self::class, 'resolveItems']);
        \add_action('admin_head-nav-menus.php', [self::class, 'addMetaBox']);
    }

    /**
     * @internal Hooked to `wp_nav_menu_objects`.
     *
     * @param array<mixed> $items
     * @return array<mixed>
     */
    public static function resolveItems(array $items): array
    {
        $loggedIn = \is_user_logged_in();
        $home = Pages::url(Pages::PORTAL) ?? \home_url('/');

        return self::apply(
            $items,
            $loggedIn,
            $loggedIn ? \wp_logout_url($home) : (Pages::url(Pages::LOGIN) ?? $home)
        );
    }

    /**
     * Sets title and URL of the login/logout items.
     *
     * @param array<mixed> $items
     * @return array<mixed>
     */
    public static function apply(array $items, bool $loggedIn, string $url): array
    {
        foreach ($items as $item) {
            if (
                !\is_object($item)
                || !\property_exists($item, 'url')
                || !\property_exists($item, 'title')
                || $item->url !== self::URL
            ) {
                continue;
            }
            $item->title = $loggedIn ? \__('Log out', 'orchestra-core') : \__('Log in', 'orchestra-core');
            $item->url = $url;
        }

        return $items;
    }

    /**
     * @internal Hooked to `admin_head-nav-menus.php`.
     */
    public static function addMetaBox(): void
    {
        \add_meta_box(
            'orchestra-login-menu-item',
            \__('Orchestra', 'orchestra-core'),
            [self::class, 'renderMetaBox'],
            'nav-menus',
            'side',
            'default'
        );
    }

    /**
     * The box uses the markup of WordPress's own boxes, so that "Add to Menu" works without script.
     *
     * @internal Meta box callback.
     */
    public static function renderMetaBox(): void
    {
        $title = \__('Log in / Log out', 'orchestra-core');
        $field = static fn (string $name, string $value): string => '<input type="hidden" class="menu-item-' . $name
            . '" name="menu-item[-1][menu-item-' . $name . ']" value="' . \esc_attr($value) . '">';

        echo '<div id="posttype-orchestra-login" class="posttypediv">'
            . '<div id="tabs-panel-orchestra-login" class="tabs-panel tabs-panel-active">'
            . '<ul id="orchestra-login-checklist" class="categorychecklist form-no-clear"><li>'
            . '<label class="menu-item-title"><input type="checkbox" class="menu-item-checkbox"'
            . ' name="menu-item[-1][menu-item-object-id]" value="-1"> ' . \esc_html($title) . '</label>'
            . $field('type', 'custom') . $field('title', \__('Log in', 'orchestra-core'))
            . $field('url', self::URL)
            . '</li></ul></div>'
            . '<p class="description">' . \esc_html__(
                'Shows "Log in" to visitors and "Log out" to logged-in users.',
                'orchestra-core'
            ) . '</p>'
            . '<p class="button-controls wp-clearfix"><span class="add-to-menu">'
            . '<input type="submit" class="button submit-add-to-menu right" value="'
            . \esc_attr__('Add to Menu', 'orchestra-core') . '" name="add-post-type-menu-item"'
            . ' id="submit-posttype-orchestra-login"><span class="spinner"></span></span></p></div>';
    }
}
