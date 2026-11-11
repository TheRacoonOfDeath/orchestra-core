<?php

/**
 * Removes the Orchestra roles, capabilities and options when the plugin is deleted. The email log
 * belongs to orchestra-email, which took it over from orchestra-core 0.6, and is kept.
 *
 * Deactivating the plugin intentionally keeps them, so that role assignments survive.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Orchestra\Core\Authorization\CapabilitySynchronizer;

CapabilitySynchronizer::removeAll();

delete_option('orchestra_core_pages');
delete_option('orchestra_core_page_setup_dismissed');
delete_option('orchestra_core_language');
delete_option('orchestra_core_private_directories');
wp_clear_scheduled_hook('orchestra_core_check_private_directories');
wp_clear_scheduled_hook('orchestra_core_check_private_directories', [true]);

delete_option('orchestra_core_github_token');
delete_option('external_updates-orchestra-core');

// Short-lived transients that may hold names (notices, login errors) or user IDs (reset emails).
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_orchestra\\_core\\_%'"
    . " OR option_name LIKE '\\_transient\\_timeout\\_orchestra\\_core\\_%'"
);
