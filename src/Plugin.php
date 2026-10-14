<?php

declare(strict_types=1);

namespace Orchestra\Core;

use Orchestra\Core\Authorization\CapabilityRegistry;
use Orchestra\Core\Authorization\CapabilitySynchronizer;
use Orchestra\Core\Authorization\MetaCapabilityFilter;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

final class Plugin
{
    /**
     * Fired on `init` with the {@see CapabilityRegistry}. Orchestra plugins register their capabilities here.
     */
    public const REGISTER_CAPABILITIES_ACTION = 'orchestra_core_register_capabilities';

    private const GITHUB_REPOSITORY = 'https://github.com/TheRacoonOfDeath/orchestra-core/';

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

    private function register(): void
    {
        $this->registerUpdateChecker();

        \add_action('init', [$this, 'registerCapabilities'], 5);
        \add_filter('map_meta_cap', new MetaCapabilityFilter($this->capabilities), 10, 4);
        \add_action('admin_init', [$this, 'deleteLegacyGithubToken']);
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
        $updateChecker = PucFactory::buildUpdateChecker(self::GITHUB_REPOSITORY, $this->pluginFile, 'orchestra-core');

        // The factory is typed to return any kind of update checker; branches and release
        // assets are only supported by the GitHub-based one.
        if (!\method_exists($updateChecker, 'setBranch') || !\method_exists($updateChecker, 'getVcsApi')) {
            return;
        }

        $updateChecker->setBranch('main');

        $vcsApi = $updateChecker->getVcsApi();
        if (\is_object($vcsApi) && \method_exists($vcsApi, 'enableReleaseAssets')) {
            $vcsApi->enableReleaseAssets();
        }
    }
}
