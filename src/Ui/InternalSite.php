<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

use Orchestra\Core\Authorization\Roles;
use WP_Post;
use WP_User;

/**
 * The Orchestra pages are internal, also when they are an area inside a public website: only they
 * are hidden, the rest of the site is left alone.
 *
 * - Pages (and posts) with an Orchestra shortcode have `noindex`, are left out of the sitemaps and
 *   cannot be embedded (oEmbed).
 * - The author archives of users with an Orchestra role who have published nothing are not found,
 *   so visitors cannot find out who has an account. WordPress already hides such users in the REST
 *   API and the users sitemap.
 *
 * A site that is only the orchestra hides everything with WordPress's "Discourage search engines"
 * (Settings → Reading).
 */
final class InternalSite
{
    private const SHORTCODE_PREFIX = '[orchestra_';

    public static function register(): void
    {
        \add_filter('wp_robots', [self::class, 'robots']);
        \add_filter('wp_sitemaps_posts_query_args', [self::class, 'sitemapQueryArgs']);
        \add_filter('oembed_request_post_id', [self::class, 'oembedPostId']);
        \add_filter('oembed_discovery_links', [self::class, 'oembedDiscoveryLinks']);
        \add_action('template_redirect', [self::class, 'notFound'], 1);
    }

    /**
     * Whether content contains an Orchestra shortcode.
     */
    public static function isOrchestraContent(string $content): bool
    {
        return \str_contains($content, self::SHORTCODE_PREFIX);
    }

    /**
     * Whether the author archive of a user is hidden: users with an Orchestra role who have
     * published nothing. Authors of the website keep their archive.
     *
     * @param list<string> $roles
     */
    public static function hidesAuthor(array $roles, int $publishedPosts): bool
    {
        return \array_intersect($roles, Roles::GRANTABLE) !== [] && $publishedPosts === 0;
    }

    /**
     * @internal Hooked to `wp_robots`.
     *
     * @param array<string, bool|string> $robots
     * @return array<string, bool|string>
     */
    public static function robots(array $robots): array
    {
        return self::isOrchestraSingular() ? \wp_robots_no_robots($robots) : $robots;
    }

    /**
     * @internal Hooked to `wp_sitemaps_posts_query_args`.
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public static function sitemapQueryArgs(array $args): array
    {
        return self::withoutPosts($args, Pages::orchestraPostIds());
    }

    /**
     * Adds posts to the `post__not_in` of query arguments.
     *
     * @param array<string, mixed> $args
     * @param list<int> $postIds
     * @return array<string, mixed>
     */
    public static function withoutPosts(array $args, array $postIds): array
    {
        if ($postIds === []) {
            return $args;
        }
        $excluded = \is_array($args['post__not_in'] ?? null)
            ? \array_map('intval', \array_filter($args['post__not_in'], 'is_numeric'))
            : [];
        $args['post__not_in'] = \array_values(\array_unique([...$excluded, ...$postIds]));

        return $args;
    }

    /**
     * Orchestra pages cannot be embedded: the oEmbed endpoint does not find them.
     *
     * @internal Hooked to `oembed_request_post_id`.
     */
    public static function oembedPostId(int $postId): int
    {
        $post = \get_post($postId);

        return $post instanceof WP_Post && self::isOrchestraContent($post->post_content) ? 0 : $postId;
    }

    /**
     * @internal Hooked to `oembed_discovery_links`.
     */
    public static function oembedDiscoveryLinks(string $links): string
    {
        return self::isOrchestraSingular() ? '' : $links;
    }

    /**
     * The embed view of Orchestra pages and the hidden author archives are not found.
     *
     * @internal Hooked to `template_redirect`, before the redirect of `?author=` to the archive.
     */
    public static function notFound(): void
    {
        global $wp_query;
        if (!$wp_query instanceof \WP_Query) {
            return;
        }
        $hidden = (\is_embed() && self::isOrchestraSingular()) || (\is_author() && self::isHiddenAuthor());
        if (!$hidden) {
            return;
        }

        $wp_query->set_404();
        \status_header(404);
        \nocache_headers();
    }

    private static function isOrchestraSingular(): bool
    {
        $post = \is_singular() ? \get_queried_object() : null;

        return $post instanceof WP_Post && self::isOrchestraContent($post->post_content);
    }

    private static function isHiddenAuthor(): bool
    {
        $user = \get_queried_object();
        if (!$user instanceof WP_User) {
            return false;
        }
        $publicTypes = \array_values(\get_post_types(['public' => true]));

        return self::hidesAuthor(
            \array_values($user->roles),
            (int) \count_user_posts($user->ID, $publicTypes, true)
        );
    }
}
