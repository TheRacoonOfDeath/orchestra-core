<?php

/**
 * Removes the Orchestra roles, capabilities and options when the plugin is deleted.
 *
 * Deactivating the plugin intentionally keeps them, so that role assignments survive.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

Orchestra\Core\Authorization\CapabilitySynchronizer::removeAll();

delete_option('orchestra_core_github_token');
