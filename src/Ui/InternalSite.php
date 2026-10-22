<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * The Orchestra pages are internal: search engines are kept out, and visitors cannot find out who
 * has an account. Independent of the "Discourage search engines" setting:
 *
 * - every page has `noindex`, and robots.txt disallows everything
 * - no sitemaps and no feeds
 * - no author archives, no user list in the REST API for visitors, no author in oEmbed data
 *
 * The filter `orchestra_core_internal_site` (bool) turns this off, e.g. for a public website on the
 * same WordPress installation.
 */
final class InternalSite
{
    public const FILTER = 'orchestra_core_internal_site';

    private const USER_ROUTES = ['/wp/v2/users', '/wp/v2/users/(?P<id>[\d]+)'];

    public static function register(): void
    {
        \add_action('init', [self::class, 'registerHooks']);
    }

    /**
     * @internal Hooked to `init`, so that themes and plugins can use the filter.
     */
    public static function registerHooks(): void
    {
        if (\apply_filters(self::FILTER, true) !== true) {
            return;
        }

        \add_filter('wp_robots', 'wp_robots_no_robots');
        \add_filter('robots_txt', [self::class, 'robotsTxt']);
        \add_filter('wp_sitemaps_enabled', '__return_false');
        \remove_action('wp_head', 'feed_links', 2);
        \remove_action('wp_head', 'feed_links_extra', 3);
        \add_action('template_redirect', [self::class, 'notFound'], 1);
        \add_filter('rest_endpoints', [self::class, 'restEndpoints']);
        \add_filter('oembed_response_data', [self::class, 'withoutAuthor']);
    }

    /**
     * @internal Hooked to `robots_txt`.
     */
    public static function robotsTxt(): string
    {
        return "User-agent: *\nDisallow: /\n";
    }

    /**
     * Feeds and author archives are not found.
     *
     * @internal Hooked to `template_redirect`, before the redirect of `?author=`.
     */
    public static function notFound(): void
    {
        global $wp_query;
        if (!(\is_feed() || \is_author()) || !$wp_query instanceof \WP_Query) {
            return;
        }

        $wp_query->set_404();
        \status_header(404);
        \nocache_headers();
    }

    /**
     * @internal Hooked to `rest_endpoints`.
     *
     * @param array<string, mixed> $endpoints
     * @return array<string, mixed>
     */
    public static function restEndpoints(array $endpoints): array
    {
        return \is_user_logged_in() ? $endpoints : self::withoutUserRoutes($endpoints);
    }

    /**
     * @param array<string, mixed> $endpoints
     * @return array<string, mixed>
     */
    public static function withoutUserRoutes(array $endpoints): array
    {
        return \array_diff_key($endpoints, \array_flip(self::USER_ROUTES));
    }

    /**
     * @internal Hooked to `oembed_response_data`.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function withoutAuthor(array $data): array
    {
        unset($data['author_name'], $data['author_url']);

        return $data;
    }
}
