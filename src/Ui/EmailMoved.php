<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * Up to 0.6, orchestra-core contained the email service; it is now the plugin orchestra-email.
 * Sites that used it are asked to install the plugin, which takes over their log and queue.
 */
final class EmailMoved
{
    /** Set by the email service of orchestra-core 0.6 and earlier; kept by orchestra-email. */
    private const EMAIL_SCHEMA_OPTION = 'orchestra_core_schema_version';

    private const EMAIL_PLUGIN = 'Orchestra\Email\Plugin';

    private const DOWNLOAD_URL = 'https://github.com/TheRacoonOfDeath/orchestra-email/releases/latest';

    public static function register(): void
    {
        \add_action('admin_notices', [self::class, 'render']);
    }

    /**
     * @internal Hooked to `admin_notices`.
     */
    public static function render(): void
    {
        if (
            \class_exists(self::EMAIL_PLUGIN)
            || \get_option(self::EMAIL_SCHEMA_OPTION) === false
            || !\current_user_can('activate_plugins')
        ) {
            return;
        }

        echo '<div class="notice notice-warning"><p>' . \esc_html__(
            'Emails are now a plugin of their own: Orchestra Email. Install and activate it to write emails'
                . ' again; your sent emails and emails that are still being sent are kept.',
            'orchestra-core'
        ) . ' <a href="' . \esc_url(self::DOWNLOAD_URL) . '">'
            . \esc_html__('Download Orchestra Email', 'orchestra-core') . '</a></p></div>';
    }
}
