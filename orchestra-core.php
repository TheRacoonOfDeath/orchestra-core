<?php

/**
 * Plugin Name: Orchestra Core
 * Plugin URI:  https://github.com/TheRacoonOfDeath/orchestra-core
 * Description: Core infrastructure plugin providing shared services for the Orchestra plugin suite.
 * Version:          0.8.1
 * Author:      Julian Nickerl
 * License:     GPL-2.0-or-later
 * Text Domain: orchestra-core
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.3
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

Orchestra\Core\Plugin::boot(__FILE__);
