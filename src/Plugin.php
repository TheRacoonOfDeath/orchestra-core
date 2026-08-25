<?php

declare(strict_types=1);

namespace Orchestra\Core;

class Plugin
{
    private const OPTION_GROUP = 'orchestra_core';
    private const OPTION_GITHUB_TOKEN = 'orchestra_core_github_token';
    private const SETTINGS_PAGE_SLUG = 'orchestra-core';

    private static ?self $instance = null;

    private function __construct()
    {
        $this->register();
    }

    public static function boot(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function register(): void
    {
        $this->registerUpdateChecker();

        if (\is_admin()) {
            $this->registerAdmin();
        }
    }

    private function registerUpdateChecker(): void
    {
        $updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/TheRacoonOfDeath/orchestra-core/',
            dirname(__DIR__) . '/orchestra-core.php',
            'orchestra-core'
        );

        $updateChecker->setBranch('main');

        $githubToken = $this->getGithubToken();

        if ($githubToken !== '') {
            $updateChecker->setAuthentication($githubToken);
        }

        $updateChecker->getVcsApi()->enableReleaseAssets();
    }

    private function registerAdmin(): void
    {
        \add_action('admin_init', [$this, 'registerSettings']);
        \add_action('admin_menu', [$this, 'registerSettingsPage']);
    }

    public function registerSettings(): void
    {
        \register_setting(
            self::OPTION_GROUP,
            self::OPTION_GITHUB_TOKEN,
            [
                'type' => 'string',
                'sanitize_callback' => [$this, 'sanitizeGithubToken'],
                'default' => '',
            ]
        );

        \add_settings_section(
            'orchestra_core_github',
            'GitHub Updates',
            [$this, 'renderGithubSettingsSection'],
            self::SETTINGS_PAGE_SLUG
        );

        \add_settings_field(
            self::OPTION_GITHUB_TOKEN,
            'GitHub token',
            [$this, 'renderGithubTokenField'],
            self::SETTINGS_PAGE_SLUG,
            'orchestra_core_github'
        );
    }

    public function registerSettingsPage(): void
    {
        \add_options_page(
            'Orchestra Core',
            'Orchestra Core',
            'manage_options',
            self::SETTINGS_PAGE_SLUG,
            [$this, 'renderSettingsPage']
        );
    }

    public function sanitizeGithubToken(mixed $value): string
    {
        if (! \is_string($value)) {
            return '';
        }

        return \trim($value);
    }

    public function renderSettingsPage(): void
    {
        if (! \current_user_can('manage_options')) {
            \wp_die(\esc_html__('You are not allowed to manage these settings.', 'orchestra-core'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo \esc_html__('Orchestra Core', 'orchestra-core'); ?></h1>
            <form action="options.php" method="post">
                <?php
                \settings_fields(self::OPTION_GROUP);
                \do_settings_sections(self::SETTINGS_PAGE_SLUG);
                \submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    public function renderGithubSettingsSection(): void
    {
        echo '<p>' . \esc_html__('Configure a GitHub token for authenticated update checks. If ORCHESTRA_GITHUB_TOKEN is defined in wp-config.php, that value takes precedence over this setting.', 'orchestra-core') . '</p>';
    }

    public function renderGithubTokenField(): void
    {
        $token = (string) \get_option(self::OPTION_GITHUB_TOKEN, '');
        ?>
        <input
            type="password"
            name="<?php echo \esc_attr(self::OPTION_GITHUB_TOKEN); ?>"
            value="<?php echo \esc_attr($token); ?>"
            class="regular-text code"
            autocomplete="off"
            spellcheck="false"
        />
        <p class="description">
            <?php echo \esc_html__('Use a fine-grained personal access token with read-only access to this repository.', 'orchestra-core'); ?>
        </p>
        <?php
    }

    private function getGithubToken(): string
    {
        if (\defined('ORCHESTRA_GITHUB_TOKEN') && \is_string(ORCHESTRA_GITHUB_TOKEN) && ORCHESTRA_GITHUB_TOKEN !== '') {
            return ORCHESTRA_GITHUB_TOKEN;
        }

        return (string) \get_option(self::OPTION_GITHUB_TOKEN, '');
    }
}
