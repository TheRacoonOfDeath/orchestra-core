<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * The shared stylesheet and script of all Orchestra pages and screens.
 *
 * They are enqueued in the head of every page whose content contains an Orchestra shortcode, and
 * on the Orchestra screens in wp-admin. Shortcodes call {@see enqueue()} as well, for content that
 * is rendered elsewhere (e.g. in widgets).
 */
final class Assets
{
    public const HANDLE = 'orchestra-ui';

    private static string $baseUrl = '';
    private static string $version = '';

    public static function register(string $pluginFile): void
    {
        self::$baseUrl = \plugins_url('assets/', $pluginFile);
        // Changes with every release, so browsers load the new files.
        self::$version = (string) @\filemtime(\dirname($pluginFile) . '/assets/orchestra-ui.css');

        \add_action('wp_enqueue_scripts', [self::class, 'enqueueForContent']);
        \add_action('admin_enqueue_scripts', [self::class, 'enqueueForAdmin']);
    }

    public static function enqueue(): void
    {
        if (self::$baseUrl === '') {
            return;
        }

        \wp_enqueue_style(self::HANDLE, self::$baseUrl . 'orchestra-ui.css', [], self::$version);
        \wp_enqueue_script(self::HANDLE, self::$baseUrl . 'orchestra-ui.js', [], self::$version, ['in_footer' => true]);
    }

    /**
     * The Orchestra screens in wp-admin: pages and post types whose name contains "orchestra".
     *
     * @internal Hooked to `admin_enqueue_scripts`.
     */
    public static function enqueueForAdmin(): void
    {
        $screen = \function_exists('get_current_screen') ? \get_current_screen() : null;
        if ($screen !== null && \str_contains($screen->id, 'orchestra')) {
            self::enqueue();
        }
    }

    /**
     * @internal Hooked to `wp_enqueue_scripts`.
     */
    public static function enqueueForContent(): void
    {
        $post = \get_post();
        if (\is_singular() && $post !== null && \str_contains($post->post_content, '[orchestra_')) {
            self::enqueue();
        }
    }
}
