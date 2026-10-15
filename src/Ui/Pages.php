<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * Finds the pages that contain the Orchestra shortcodes, so that Orchestra pages can link to each
 * other without any configuration.
 */
final class Pages
{
    public const PORTAL = 'orchestra_portal';
    public const LOGIN = 'orchestra_login';

    private const CACHE_OPTION = 'orchestra_core_pages';

    /** @var array<string, int>|null shortcode => page ID (0 if there is none) */
    private static ?array $cache = null;

    public static function register(): void
    {
        foreach (['save_post_page', 'deleted_post', 'trashed_post', 'untrashed_post'] as $hook) {
            \add_action($hook, [self::class, 'flush']);
        }
    }

    /**
     * The URL of the published page containing the shortcode, or null.
     */
    public static function url(string $shortcode): ?string
    {
        $pageId = self::pageId($shortcode);
        if ($pageId === 0) {
            return null;
        }
        $url = \get_permalink($pageId);

        return \is_string($url) ? $url : null;
    }

    public static function pageId(string $shortcode): int
    {
        if (self::$cache === null) {
            $cached = \get_option(self::CACHE_OPTION);
            self::$cache = \is_array($cached)
                ? \array_map('intval', \array_filter($cached, 'is_numeric'))
                : [];
        }
        if (!isset(self::$cache[$shortcode])) {
            self::$cache[$shortcode] = self::find($shortcode);
            \update_option(self::CACHE_OPTION, self::$cache, true);
        }

        return self::$cache[$shortcode];
    }

    /**
     * @internal Hooked to changes of pages.
     */
    public static function flush(): void
    {
        self::$cache = null;
        \delete_option(self::CACHE_OPTION);
    }

    private static function find(string $shortcode): int
    {
        global $wpdb;
        if (!$wpdb instanceof \wpdb) {
            return 0;
        }

        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM %i WHERE post_type = 'page' AND post_status = 'publish'"
                . ' AND (post_content LIKE %s OR post_content LIKE %s) ORDER BY menu_order, ID LIMIT 1',
            $wpdb->posts,
            '%' . $wpdb->esc_like('[' . $shortcode . ']') . '%',
            '%' . $wpdb->esc_like('[' . $shortcode . ' ') . '%'
        ));

        return \is_numeric($id) ? (int) $id : 0;
    }
}
