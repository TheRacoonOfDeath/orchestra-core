<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * Offers administrators to create the Orchestra pages with one click: a start page and one page
 * per area, each with the navigation on top.
 */
final class PageSetup
{
    private const ACTION = 'orchestra_core_create_pages';
    private const DISMISS_ACTION = 'orchestra_core_dismiss_page_setup';
    private const DISMISSED_OPTION = 'orchestra_core_page_setup_dismissed';

    public static function register(): void
    {
        \add_action('admin_notices', [self::class, 'renderNotice']);
        \add_action('admin_post_' . self::ACTION, [self::class, 'createPages']);
        \add_action('admin_post_' . self::DISMISS_ACTION, [self::class, 'dismiss']);
    }

    /**
     * @internal Hooked to `admin_notices`.
     */
    public static function renderNotice(): void
    {
        if (
            !\current_user_can('publish_pages')
            || Pages::url(Pages::PORTAL) !== null
            || \get_option(self::DISMISSED_OPTION) !== false
        ) {
            return;
        }

        echo '<div class="notice notice-info"><p><strong>' . \esc_html__('Orchestra', 'orchestra-core') . ':</strong> '
            . \esc_html__(
                'Members, conductors and organizers use the Orchestra pages instead of the dashboard. Create them?',
                'orchestra-core'
            ) . '</p><p>'
            . self::button(self::ACTION, \__('Create Orchestra pages', 'orchestra-core'), 'button button-primary') . ' '
            . self::button(self::DISMISS_ACTION, \__('I will add the shortcodes myself', 'orchestra-core'), 'button')
            . '</p></div>';
    }

    /**
     * @internal Hooked to `admin_post_orchestra_core_create_pages`.
     */
    public static function createPages(): never
    {
        \check_admin_referer(self::ACTION);
        if (!\current_user_can('publish_pages')) {
            \wp_die(\esc_html__('You are not allowed to create pages.', 'orchestra-core'), 403);
        }

        self::createMissingPages();

        Notices::success(\__('The Orchestra pages have been created.', 'orchestra-core'));
        \wp_safe_redirect(\admin_url('edit.php?post_type=page'));
        exit;
    }

    /**
     * @internal Hooked to `admin_post_orchestra_core_dismiss_page_setup`.
     */
    public static function dismiss(): never
    {
        \check_admin_referer(self::DISMISS_ACTION);
        if (\current_user_can('publish_pages')) {
            \update_option(self::DISMISSED_OPTION, 1, false);
        }

        \wp_safe_redirect((string) (\wp_get_referer() ?: \admin_url()));
        exit;
    }

    /**
     * Creates the start page, the login page and a page for every menu item that has none yet.
     */
    public static function createMissingPages(): void
    {
        $portal = Pages::pageId(Pages::PORTAL);
        if ($portal === 0) {
            $portal = self::create(\__('Orchestra', 'orchestra-core'), '[' . Pages::PORTAL . ']', 0, 0);
        }
        if (Pages::pageId(Pages::LOGIN) === 0) {
            self::create(\__('Login', 'orchestra-core'), '[' . Pages::LOGIN . ']', $portal, 99);
        }
        foreach (Menu::items() as $item) {
            if (Pages::pageId($item->shortcode) === 0) {
                $content = '[' . NavigationShortcodes::MENU . "]\n\n[" . $item->shortcode . ']';
                self::create($item->label, $content, $portal, $item->position);
            }
        }
        Pages::flush();
    }

    private static function create(string $title, string $content, int $parent, int $order): int
    {
        return \wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => $content,
            'post_parent' => $parent,
            'menu_order' => $order,
            'comment_status' => 'closed',
        ]);
    }

    private static function button(string $action, string $label, string $class): string
    {
        return '<a class="' . \esc_attr($class) . '" href="'
            . \esc_url(\wp_nonce_url(\admin_url('admin-post.php?action=' . $action), $action)) . '">'
            . \esc_html($label) . '</a>';
    }
}
