<?php

declare(strict_types=1);

namespace Orchestra\Core\Files;

use Closure;
use Throwable;

/**
 * Checks that the private directories of the Orchestra plugins (sheet music, email attachments)
 * cannot be downloaded through a URL. Their `.htaccess` only protects them on Apache; nginx ignores
 * it, also when it runs in front of Apache.
 *
 * Plugins add their directories during {@see REGISTER_ACTION}:
 *
 *     add_action(PrivateDirectoryCheck::REGISTER_ACTION, function (PrivateDirectoryCheck $check): void {
 *         $check->add('orchestra_library', 'Sheet music', 'ORCHESTRA_LIBRARY_STORAGE_DIR', $directory);
 *     });
 *
 * A directory under the web root is checked by writing a test file and requesting it over HTTP.
 * The result is shown in Site Health (Tools → Site Health) and, while a directory is public, in a
 * notice for administrators. The check runs daily with WP-Cron, at once when a directory changes,
 * and every time Site Health is opened.
 */
final class PrivateDirectoryCheck
{
    public const REGISTER_ACTION = 'orchestra_core_register_private_directories';
    public const CRON_HOOK = 'orchestra_core_check_private_directories';
    public const OPTION = 'orchestra_core_private_directories';

    public const PRIVATE = 'private';
    public const OUTSIDE = 'outside';
    public const PUBLIC = 'public';
    public const UNKNOWN = 'unknown';

    private const TEST = 'orchestra_private_files';
    private const PROBE_PREFIX = 'orchestra-check-';

    /** @var array<string, array{label: string, constant: string, directory: Closure(): string}>|null */
    private static ?array $directories = null;

    public static function register(): void
    {
        \add_action('init', [self::class, 'schedule']);
        \add_action(self::CRON_HOOK, [self::class, 'runCheck']);
        \add_filter('site_status_tests', [self::class, 'addTest']);
        \add_action('admin_notices', [self::class, 'notice']);
    }

    /**
     * Adds a private directory.
     *
     * @param string $label Shown to administrators, e.g. "Sheet music"
     * @param string $constant The `wp-config.php` constant that moves the directory
     * @param Closure(): string $directory Returns the directory, created with its protection files
     */
    public function add(string $id, string $label, string $constant, Closure $directory): self
    {
        self::$directories[$id] = ['label' => $label, 'constant' => $constant, 'directory' => $directory];

        return $this;
    }

    /**
     * The URL under which a directory would be served, or null if it is outside the web root.
     *
     * @param array<string, string> $webRoots path => URL, the most specific first
     */
    public static function urlFor(string $directory, array $webRoots): ?string
    {
        $directory = \rtrim(\str_replace('\\', '/', $directory), '/');
        foreach ($webRoots as $root => $url) {
            $root = \rtrim(\str_replace('\\', '/', $root), '/');
            if ($root === '' || ($directory !== $root && !\str_starts_with($directory, $root . '/'))) {
                continue;
            }
            $segments = \array_filter(
                \explode('/', \substr($directory, \strlen($root))),
                static fn (string $segment): bool => $segment !== ''
            );

            return \rtrim($url, '/') . \implode('', \array_map(
                static fn (string $segment): string => '/' . \rawurlencode($segment),
                $segments
            ));
        }

        return null;
    }

    /**
     * The status of a probe: public only if the secret content came back.
     */
    public static function statusOfProbe(int $responseCode, string $body, string $secret): string
    {
        return $responseCode === 200 && \str_contains($body, $secret) ? self::PUBLIC : self::PRIVATE;
    }

    /**
     * @internal Hooked to `init`.
     */
    public static function schedule(): void
    {
        if (\wp_next_scheduled(self::CRON_HOOK) === false) {
            \wp_schedule_event(\time() + \MINUTE_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    /**
     * @internal Hooked to the cron event.
     */
    public static function runCheck(): void
    {
        self::checkAll();
    }

    /**
     * Checks every directory and stores the results.
     *
     * @return array<string, array{status: string, directory: string}>
     */
    private static function checkAll(): array
    {
        $results = [];
        foreach (self::directories() as $id => $entry) {
            try {
                $directory = self::resolve(($entry['directory'])());
                $results[$id] = ['status' => self::check($directory), 'directory' => $directory];
            } catch (Throwable) {
                $results[$id] = ['status' => self::UNKNOWN, 'directory' => ''];
            }
        }
        \update_option(self::OPTION, $results, false);

        return $results;
    }

    /**
     * @internal Hooked to `site_status_tests`.
     *
     * @param array<string, mixed> $tests
     * @return array<string, mixed>
     */
    public static function addTest(array $tests): array
    {
        if (self::directories() === []) {
            return $tests;
        }
        $direct = \is_array($tests['direct'] ?? null) ? $tests['direct'] : [];
        $direct[self::TEST] = [
            'label' => \__('Orchestra files are private', 'orchestra-core'),
            'test' => [self::class, 'siteHealthResult'],
        ];
        $tests['direct'] = $direct;

        return $tests;
    }

    /**
     * @internal Site Health test callback.
     *
     * @return array<string, mixed>
     */
    public static function siteHealthResult(): array
    {
        $results = self::checkAll();
        $public = \array_keys(\array_filter($results, static fn (array $r): bool => $r['status'] === self::PUBLIC));
        $unknown = \array_keys(\array_filter($results, static fn (array $r): bool => $r['status'] === self::UNKNOWN));
        $badge = ['label' => \__('Security', 'orchestra-core'), 'color' => 'blue'];

        if ($public !== []) {
            return [
                'label' => \__('Private Orchestra files can be downloaded by anyone', 'orchestra-core'),
                'status' => 'critical',
                'badge' => ['color' => 'red'] + $badge,
                'description' => '<p>' . \esc_html__(
                    'Anyone who knows the address of a file in these folders can download it without logging in.',
                    'orchestra-core'
                ) . ' ' . \esc_html__(
                    'The web server ignores their .htaccess file, e.g. because it is nginx.',
                    'orchestra-core'
                ) . '</p>' . self::instructions($public, $results),
                'actions' => '',
                'test' => self::TEST,
            ];
        }
        if ($unknown !== []) {
            return [
                'label' => \__('Whether the Orchestra files are private could not be checked', 'orchestra-core'),
                'status' => 'recommended',
                'badge' => $badge,
                'description' => '<p>' . \esc_html__(
                    'The site could not request a test file from these folders, e.g. because it cannot reach itself.',
                    'orchestra-core'
                ) . ' ' . \esc_html__(
                    'Check them yourself, or move them outside the web root.',
                    'orchestra-core'
                ) . '</p>' . self::instructions($unknown, $results),
                'actions' => '',
                'test' => self::TEST,
            ];
        }

        return [
            'label' => \__('Orchestra files are private', 'orchestra-core'),
            'status' => 'good',
            'badge' => $badge,
            'description' => '<p>' . \esc_html__(
                'Sheet music and email attachments cannot be downloaded through their folders.',
                'orchestra-core'
            ) . ' ' . \esc_html__(
                'They are only delivered to users who may see them.',
                'orchestra-core'
            ) . '</p>',
            'actions' => '',
            'test' => self::TEST,
        ];
    }

    /**
     * Warns administrators while a directory is public, and checks again at once when a directory
     * has changed, e.g. after its constant was set.
     *
     * @internal Hooked to `admin_notices`.
     */
    public static function notice(): void
    {
        if (!\current_user_can('manage_options') || self::directories() === []) {
            return;
        }
        $stored = \get_option(self::OPTION);
        $stored = \is_array($stored) ? $stored : [];

        $public = [];
        $outdated = false;
        foreach (self::directories() as $id => $entry) {
            try {
                $directory = self::resolve(($entry['directory'])());
            } catch (Throwable) {
                continue;
            }
            $result = \is_array($stored[$id] ?? null) ? $stored[$id] : [];
            if (($result['directory'] ?? null) !== $directory) {
                $outdated = true;
            } elseif (($result['status'] ?? null) === self::PUBLIC) {
                $public[] = $entry['label'];
            }
        }
        if ($outdated && \wp_next_scheduled(self::CRON_HOOK, [true]) === false) {
            \wp_schedule_single_event(\time(), self::CRON_HOOK, [true]);
        }
        if ($public === []) {
            return;
        }

        echo '<div class="notice notice-error"><p><strong>' . \esc_html__('Orchestra', 'orchestra-core')
            . ':</strong> ' . \esc_html(\sprintf(
                /* translators: %s: list of folders, e.g. "Sheet music, Email attachments" */
                \__('Private files can be downloaded without logging in (%s).', 'orchestra-core'),
                \implode(', ', $public)
            )) . ' <a href="' . \esc_url(\admin_url('site-health.php')) . '">'
            . \esc_html__('Site Health explains how to protect them.', 'orchestra-core')
            . '</a></p></div>';
    }

    /**
     * Only for tests.
     */
    public static function reset(): void
    {
        self::$directories = null;
    }

    /**
     * The real path, so that the same directory compares equal when it is reached through a
     * symbolic link, e.g. by WP-CLI and the web server.
     */
    private static function resolve(string $directory): string
    {
        $real = \realpath($directory);

        return $real === false ? $directory : $real;
    }

    private static function check(string $directory): string
    {
        $url = self::urlFor($directory, self::webRoots());
        if ($url === null) {
            return self::OUTSIDE;
        }

        // Without extension, like the stored files.
        $name = self::PROBE_PREFIX . \strtolower(\wp_generate_password(20, false));
        $secret = \wp_generate_password(32, false);
        $path = $directory . '/' . $name;
        if (\file_put_contents($path, $secret) === false) {
            return self::UNKNOWN;
        }
        try {
            $response = \wp_remote_get($url . '/' . $name, [
                'timeout' => 10,
                'redirection' => 3,
                'sslverify' => \apply_filters('https_local_ssl_verify', false),
            ]);
        } finally {
            \unlink($path);
        }
        if (\is_wp_error($response)) {
            return self::UNKNOWN;
        }

        return self::statusOfProbe(
            (int) \wp_remote_retrieve_response_code($response),
            \wp_remote_retrieve_body($response),
            $secret
        );
    }

    /**
     * @return array<string, string> path => URL, the most specific first
     */
    private static function webRoots(): array
    {
        $uploads = \wp_upload_dir(null, false);
        $roots = [
            $uploads['basedir'] => $uploads['baseurl'],
            \WP_CONTENT_DIR => \content_url(),
            \ABSPATH => \site_url('/'),
        ];
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $home = \wp_parse_url(\home_url('/'));
        if (\is_string($documentRoot) && $documentRoot !== '' && \is_array($home) && isset($home['host'])) {
            $port = isset($home['port']) ? ':' . $home['port'] : '';
            $roots[$documentRoot] = ($home['scheme'] ?? 'https') . '://' . $home['host'] . $port . '/';
        }

        $resolved = [];
        foreach ($roots as $path => $url) {
            $resolved[self::resolve((string) $path)] ??= $url;
        }

        return $resolved;
    }

    /**
     * @param list<string> $ids
     * @param array<string, array{status: string, directory: string}> $results
     */
    private static function instructions(array $ids, array $results): string
    {
        $directories = self::directories();
        $list = '';
        $defines = '';
        $locations = '';
        foreach ($ids as $id) {
            $entry = $directories[$id] ?? null;
            if ($entry === null) {
                continue;
            }
            $directory = $results[$id]['directory'] ?? '';
            $list .= '<li>' . \esc_html($entry['label']) . ': <code>' . \esc_html($directory) . '</code></li>';
            $defines .= "define('" . $entry['constant'] . "', '/path/outside/webroot/" . \sanitize_key($id) . "');\n";
            $url = self::urlFor($directory, self::webRoots());
            $path = $url === null ? null : \wp_parse_url($url, \PHP_URL_PATH);
            if (\is_string($path)) {
                $locations .= 'location ^~ ' . \rtrim($path, '/') . "/ {\n    deny all;\n}\n";
            }
        }

        return '<ul>' . $list . '</ul><p>' . \esc_html__(
            'Recommended: add these lines to wp-config.php, with folders outside the web root that PHP can write to.',
            'orchestra-core'
        ) . ' ' . \esc_html__(
            'Move the files already stored into them:',
            'orchestra-core'
        ) . '</p><pre>' . \esc_html($defines) . '</pre>'
            . ($locations === '' ? '' : '<p>' . \esc_html__(
                'Or, on nginx, deny the folders in the server configuration:',
                'orchestra-core'
            ) . '</p><pre>' . \esc_html($locations) . '</pre>');
    }

    /**
     * @return array<string, array{label: string, constant: string, directory: Closure(): string}>
     */
    private static function directories(): array
    {
        if (self::$directories === null) {
            self::$directories = [];
            \do_action(self::REGISTER_ACTION, new self());
        }

        return self::$directories;
    }
}
