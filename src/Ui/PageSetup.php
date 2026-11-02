<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * Offers administrators to create the missing Orchestra pages: a start page, a login page and one
 * page per area, each area with the navigation on top. The notice lists every missing page with a
 * checkbox, so it also appears when a plugin adds a new area to a site that has the other pages.
 */
final class PageSetup
{
    private const ACTION = 'orchestra_core_create_pages';
    private const DISMISS_ACTION = 'orchestra_core_dismiss_page_setup';
    /** The shortcodes whose missing pages the administrators chose not to create */
    private const DISMISSED_OPTION = 'orchestra_core_page_setup_dismissed';
    private const FIELD_PAGES = 'pages';

    public static function register(): void
    {
        \add_action('admin_notices', [self::class, 'renderNotice']);
        \add_action('admin_post_' . self::ACTION, [self::class, 'createPages']);
        \add_action('admin_post_' . self::DISMISS_ACTION, [self::class, 'dismiss']);
    }

    /**
     * @return array<string, string> shortcode => title of the pages that do not exist yet: the start
     *         page, the login page and the page of every area
     */
    public static function missing(): array
    {
        $pages = [
            Pages::PORTAL => \__('Orchestra', 'orchestra-core'),
            Pages::LOGIN => \__('Login', 'orchestra-core'),
        ];
        foreach (Menu::items() as $item) {
            $pages[$item->shortcode] = $item->label;
        }

        return \array_filter(
            $pages,
            static fn (string $shortcode): bool => Pages::pageId($shortcode) === 0,
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return array<string, string> the missing pages the administrators have not dismissed
     */
    public static function offered(): array
    {
        return \array_diff_key(self::missing(), \array_flip(self::dismissed()));
    }

    /**
     * @internal Hooked to `admin_notices`.
     */
    public static function renderNotice(): void
    {
        $offered = self::offered();
        if (!\current_user_can('publish_pages') || $offered === []) {
            return;
        }

        $choices = '';
        foreach ($offered as $shortcode => $title) {
            $choices .= '<label style="display: block; margin: 0.3em 0;"><input type="checkbox" name="'
                . self::FIELD_PAGES . '[]" value="' . \esc_attr($shortcode) . '"> ' . \esc_html($title)
                . ' <code>[' . \esc_html($shortcode) . ']</code></label>';
        }

        echo '<div class="notice notice-info"><form method="post" action="' . \esc_url(\admin_url('admin-post.php'))
            . '"><p><strong>' . \esc_html__('Orchestra', 'orchestra-core') . ':</strong> '
            . \esc_html__(
                'Members, conductors and organizers use the Orchestra pages instead of the dashboard. These pages'
                    . ' do not exist yet. Which of them should be created?',
                'orchestra-core'
            ) . '</p>' . $choices . '<p><input type="hidden" name="action" value="' . self::ACTION . '">'
            . \wp_nonce_field(self::ACTION, '_wpnonce', true, false)
            . '<button type="submit" class="button button-primary">'
            . \esc_html__('Create selected pages', 'orchestra-core') . '</button> '
            . '<a class="button" href="' . \esc_url(\wp_nonce_url(
                \admin_url('admin-post.php?action=' . self::DISMISS_ACTION),
                self::DISMISS_ACTION
            )) . '">' . \esc_html__('I will add the shortcodes myself', 'orchestra-core') . '</a>'
            . '</p></form></div>';
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

        $chosen = \wp_unslash($_POST[self::FIELD_PAGES] ?? null);
        $chosen = \is_array($chosen) ? \array_values(\array_filter($chosen, 'is_string')) : [];
        if ($chosen === []) {
            Notices::error(\__('Choose the pages to create.', 'orchestra-core'));
            \wp_safe_redirect((string) (\wp_get_referer() ?: \admin_url()));
            exit;
        }

        self::createMissingPages($chosen);

        Notices::success(\__('The Orchestra pages have been created.', 'orchestra-core'));
        \wp_safe_redirect(\admin_url('edit.php?post_type=page'));
        exit;
    }

    /**
     * Hides the notice for the pages missing now; it appears again when another page is missing,
     * e.g. for the area of a newly activated plugin.
     *
     * @internal Hooked to `admin_post_orchestra_core_dismiss_page_setup`.
     */
    public static function dismiss(): never
    {
        \check_admin_referer(self::DISMISS_ACTION);
        if (\current_user_can('publish_pages')) {
            $dismissed = \array_values(\array_unique([...self::dismissed(), ...\array_keys(self::missing())]));
            \update_option(self::DISMISSED_OPTION, $dismissed, false);
        }

        \wp_safe_redirect((string) (\wp_get_referer() ?: \admin_url()));
        exit;
    }

    /**
     * Creates the missing pages among the given shortcodes, or all missing pages. The pages of the
     * areas are children of the start page, if there is one.
     *
     * @param list<string>|null $shortcodes
     */
    public static function createMissingPages(?array $shortcodes = null): void
    {
        $missing = self::missing();
        if ($shortcodes !== null) {
            $missing = \array_intersect_key($missing, \array_flip($shortcodes));
        }

        if (isset($missing[Pages::PORTAL])) {
            self::create($missing[Pages::PORTAL], '[' . Pages::PORTAL . ']', 0, 0);
            Pages::flush();
        }
        $portal = Pages::pageId(Pages::PORTAL);
        if (isset($missing[Pages::LOGIN])) {
            self::create($missing[Pages::LOGIN], '[' . Pages::LOGIN . ']', $portal, 99);
        }
        foreach (Menu::items() as $item) {
            if (isset($missing[$item->shortcode])) {
                $content = '[' . NavigationShortcodes::MENU . "]\n\n[" . $item->shortcode . ']';
                self::create($item->label, $content, $portal, $item->position);
            }
        }
        Pages::flush();
    }

    /**
     * @return list<string> the dismissed shortcodes; a dismissal before 0.10 (1) dismissed nothing
     *         in particular, so the missing pages are offered again
     */
    private static function dismissed(): array
    {
        $dismissed = \get_option(self::DISMISSED_OPTION);

        return \is_array($dismissed) ? \array_values(\array_filter($dismissed, 'is_string')) : [];
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
}
