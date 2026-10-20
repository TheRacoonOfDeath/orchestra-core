<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * `[orchestra_menu]`: a navigation bar to the Orchestra pages the user can use. It has no "Log out":
 * that is the "Log in / Log out" entry of the site's menu ({@see LoginMenuItem}) and the login page.
 * `[orchestra_portal]`: the start page with a card per area; the login form for visitors.
 */
final class NavigationShortcodes
{
    public const MENU = 'orchestra_menu';

    public static function register(): void
    {
        \add_shortcode(self::MENU, [self::class, 'renderMenu']);
        \add_shortcode(Pages::PORTAL, [self::class, 'renderPortal']);
    }

    /**
     * @internal Shortcode callback.
     */
    public static function renderMenu(): string
    {
        $userId = \get_current_user_id();
        if ($userId === 0) {
            return '';
        }

        $current = \get_queried_object_id();
        $html = '<nav class="orchestra-nav" aria-label="' . \esc_attr__('Orchestra', 'orchestra-core') . '">';
        $portal = Pages::url(Pages::PORTAL);
        if ($portal !== null) {
            $html .= self::link($portal, \__('Overview', 'orchestra-core'), Pages::pageId(Pages::PORTAL) === $current);
        }
        foreach (Menu::linksFor($userId) as [$item, $url]) {
            $html .= self::link($url, $item->label, Pages::pageId($item->shortcode) === $current);
        }

        return Html::page($html . '</nav>', false);
    }

    /**
     * @internal Shortcode callback.
     */
    public static function renderPortal(): string
    {
        if (!\is_user_logged_in()) {
            return Html::page(LoginShortcode::content(), false);
        }

        $user = \wp_get_current_user();
        $html = '<p>' . \esc_html(\sprintf(
            /* translators: %s: user's name */
            \__('Hello %s!', 'orchestra-core'),
            $user->display_name
        )) . '</p>';

        $links = Menu::linksFor($user->ID);
        if ($links === []) {
            return Html::page($html . Html::empty(\__(
                'There is nothing here for you yet. If you are missing something, ask an organizer.',
                'orchestra-core'
            )));
        }

        $html .= '<div class="orchestra-cards">';
        foreach ($links as [$item, $url]) {
            $html .= '<a class="orchestra-card" href="' . \esc_url($url) . '"><h3 class="orchestra-card__title">'
                . \esc_html($item->label) . '</h3><p class="orchestra-muted">' . \esc_html($item->description)
                . '</p></a>';
        }

        return Html::page($html . '</div>');
    }

    private static function link(string $url, string $label, bool $current): string
    {
        return '<a href="' . \esc_url($url) . '"' . ($current ? ' aria-current="page"' : '') . '>'
            . \esc_html($label) . '</a>';
    }
}
