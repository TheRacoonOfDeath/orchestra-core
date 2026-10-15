<?php

declare(strict_types=1);

namespace Orchestra\Core;

use Orchestra\Core\Authorization\CapabilityRegistry;
use Orchestra\Core\Authorization\CapabilitySynchronizer;
use Orchestra\Core\Authorization\MetaCapabilityFilter;
use Orchestra\Core\Email\Admin\ComposePage;
use Orchestra\Core\Email\EmailLog;
use Orchestra\Core\Email\EmailSchema;
use Orchestra\Core\Email\EmailSender;
use Orchestra\Core\Email\EmailService;
use Orchestra\Core\Email\RecipientResolver;
use Orchestra\Core\Email\Ui\EmailController;
use Orchestra\Core\Email\Ui\EmailShortcode;
use Orchestra\Core\Email\Ui\EmailView;
use Orchestra\Core\Ui\AdminAccess;
use Orchestra\Core\Ui\Assets;
use Orchestra\Core\Ui\LoginShortcode;
use Orchestra\Core\Ui\Menu;
use Orchestra\Core\Ui\MenuItem;
use Orchestra\Core\Ui\NavigationShortcodes;
use Orchestra\Core\Ui\Notices;
use Orchestra\Core\Ui\Pages;
use Orchestra\Core\Ui\PageSetup;
use LogicException;
use wpdb;
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
    private readonly EmailSchema $emailSchema;
    private readonly EmailSender $emailSender;
    private readonly EmailService $email;

    private function __construct(private readonly string $pluginFile, wpdb $wpdb)
    {
        $this->capabilities = new CapabilityRegistry();
        $this->emailSchema = new EmailSchema($wpdb);
        $emailLog = new EmailLog($wpdb, $this->emailSchema);
        $this->emailSender = new EmailSender($emailLog);
        $this->email = new EmailService($emailLog, $this->emailSender, new RecipientResolver());
    }

    public static function boot(string $pluginFile): self
    {
        if (self::$instance === null) {
            $wpdb = $GLOBALS['wpdb'] ?? null;
            if (!$wpdb instanceof wpdb) {
                throw new LogicException('orchestra-core requires a WordPress database connection.');
            }

            self::$instance = new self($pluginFile, $wpdb);
            self::$instance->register();
        }

        return self::$instance;
    }

    /**
     * The running plugin. Other plugins use it to access the {@see EmailService}.
     */
    public static function instance(): self
    {
        return self::$instance ?? throw new LogicException('orchestra-core is not loaded.');
    }

    public function email(): EmailService
    {
        return $this->email;
    }

    private function register(): void
    {
        $this->registerUpdateChecker();

        \add_action('init', [$this, 'registerCapabilities'], 5);
        \add_filter('map_meta_cap', new MetaCapabilityFilter($this->capabilities), 10, 4);
        \add_action('admin_init', [$this, 'deleteLegacyGithubToken']);

        \add_action('init', [$this, 'setUpEmail'], 1);
        \add_action(EmailSender::CRON_HOOK, [$this->emailSender, 'sendBatch']);
        \add_action(EmailService::PURGE_CRON_HOOK, [$this->email, 'purgeLog']);
        \add_action('deleted_user', [$this->email, 'anonymizeUser']);

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
        NavigationShortcodes::register();

        $emailView = new EmailView($this->email);
        (new EmailController($this->email))->register();
        (new EmailShortcode($emailView))->register();
        \add_action(Menu::REGISTER_ACTION, function (Menu $menu): void {
            $menu->add(new MenuItem(
                EmailShortcode::TAG,
                \__('Email', 'orchestra-core'),
                \__('Write to members, sections and project participants.', 'orchestra-core'),
                fn (int $userId): bool => $this->email->groups()->forUser($userId) !== [],
                80
            ));
        });

        if (\is_admin()) {
            Notices::register();
            PageSetup::register();
            (new ComposePage($emailView))->register();
        }
    }

    /**
     * @internal Hooked to `init`.
     */
    public function setUpEmail(): void
    {
        $this->emailSchema->migrateIfOutdated();
        if (\wp_next_scheduled(EmailService::PURGE_CRON_HOOK) === false) {
            \wp_schedule_event(\time() + HOUR_IN_SECONDS, 'daily', EmailService::PURGE_CRON_HOOK);
        }
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
