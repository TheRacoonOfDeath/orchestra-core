<?php

declare(strict_types=1);

namespace Orchestra\Core;

use Orchestra\Core\Authorization\CapabilityRegistry;
use Orchestra\Core\Authorization\CapabilitySynchronizer;
use Orchestra\Core\Authorization\MetaCapabilityFilter;
use Orchestra\Core\Ui\AdminAccess;
use Orchestra\Core\Ui\EmailMoved;
use Orchestra\Core\Ui\Assets;
use Orchestra\Core\Ui\Language;
use Orchestra\Core\Ui\LoginMenuItem;
use Orchestra\Core\Ui\LoginPageRedirect;
use Orchestra\Core\Ui\LoginShortcode;
use Orchestra\Core\Ui\NavigationShortcodes;
use Orchestra\Core\Ui\Notices;
use Orchestra\Core\Ui\Pages;
use Orchestra\Core\Ui\PageSetup;
use LogicException;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

final class Plugin
{
    /**
     * Fired on `init` with the {@see CapabilityRegistry}. Orchestra plugins register their capabilities here.
     */
    public const REGISTER_CAPABILITIES_ACTION = 'orchestra_core_register_capabilities';

    /**
     * Update metadata, attached to every GitHub release by build-plugin.sh. This download link
     * always points to the latest release and, unlike the GitHub API, has no rate limit for
     * requests without a token.
     */
    private const UPDATE_METADATA_URL =
        'https://github.com/TheRacoonOfDeath/orchestra-core/releases/latest/download/update.json';

    /**
     * Stored by versions up to 0.1.3, which supported authenticated update checks.
     */
    private const LEGACY_GITHUB_TOKEN_OPTION = 'orchestra_core_github_token';

    private static ?self $instance = null;

    private readonly CapabilityRegistry $capabilities;

    private function __construct(private readonly string $pluginFile)
    {
        $this->capabilities = new CapabilityRegistry();
    }

    public static function boot(string $pluginFile): self
    {
        if (self::$instance === null) {
            self::$instance = new self($pluginFile);
            self::$instance->register();
        }

        return self::$instance;
    }

    /**
     * The running plugin.
     */
    public static function instance(): self
    {
        return self::$instance ?? throw new LogicException('orchestra-core is not loaded.');
    }

    private function register(): void
    {
        $this->registerUpdateChecker();

        Language::register();
        \add_action('init', [$this, 'loadTextDomain'], 0);
        \add_action('init', [$this, 'registerCapabilities'], 5);
        \add_filter('map_meta_cap', new MetaCapabilityFilter($this->capabilities), 10, 4);
        \add_action('admin_init', [$this, 'deleteLegacyGithubToken']);

        $this->registerUi();
    }

    /**
     * The Orchestra pages (shortcodes) and the shared UI. Every function is available on normal
     * pages; wp-admin is optional and kept for administrators only.
     */
    private function registerUi(): void
    {
        Assets::register($this->pluginFile);
        Pages::register();
        AdminAccess::register();
        LoginShortcode::register();
        LoginPageRedirect::register();
        NavigationShortcodes::register();
        LoginMenuItem::register();

        if (\is_admin()) {
            Notices::register();
            PageSetup::register();
            EmailMoved::register();
        }
    }

    /**
     * @internal Hooked to `init`, before anything is translated.
     */
    public function loadTextDomain(): void
    {
        \load_plugin_textdomain('orchestra-core', false, \dirname(\plugin_basename($this->pluginFile)) . '/languages');
    }

    /**
     * @internal Hooked to `init`.
     */
    public function registerCapabilities(): void
    {
        \do_action(self::REGISTER_CAPABILITIES_ACTION, $this->capabilities);
        $this->capabilities->seal();

        (new CapabilitySynchronizer($this->capabilities))->syncIfOutdated();
    }

    /**
     * @internal Hooked to `admin_init`.
     */
    public function deleteLegacyGithubToken(): void
    {
        if (\get_option(self::LEGACY_GITHUB_TOKEN_OPTION) !== false) {
            \delete_option(self::LEGACY_GITHUB_TOKEN_OPTION);
        }
    }

    private function registerUpdateChecker(): void
    {
        PucFactory::buildUpdateChecker(self::UPDATE_METADATA_URL, $this->pluginFile, 'orchestra-core');
    }
}
