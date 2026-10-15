<?php

/**
 * Removes the Orchestra roles, capabilities, the email log and options when the plugin is deleted.
 *
 * Deactivating the plugin intentionally keeps them, so that role assignments survive.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Orchestra\Core\Authorization\CapabilitySynchronizer;
use Orchestra\Core\Email\EmailSchema;
use Orchestra\Core\Email\EmailSender;
use Orchestra\Core\Email\EmailService;

CapabilitySynchronizer::removeAll();

$orchestraCoreDatabase = $GLOBALS['wpdb'];
if ($orchestraCoreDatabase instanceof wpdb) {
    (new EmailSchema($orchestraCoreDatabase))->drop();
}
wp_clear_scheduled_hook(EmailSender::CRON_HOOK);
wp_clear_scheduled_hook(EmailService::PURGE_CRON_HOOK);
delete_option('orchestra_core_email_lock');
delete_option('orchestra_core_pages');
delete_option('orchestra_core_page_setup_dismissed');

delete_option('orchestra_core_github_token');
