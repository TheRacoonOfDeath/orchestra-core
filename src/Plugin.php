<?php

declare(strict_types=1);

namespace Orchestra\Core;

class Plugin
{
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
    }

    private function registerUpdateChecker(): void
    {
        $updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/TheRacoonOfDeath/orchestra-core/',
            dirname(__DIR__) . '/orchestra-core.php',
            'orchestra-core'
        );

        $updateChecker->setBranch('main');
        $updateChecker->getVcsApi()->enableReleaseAssets();
    }
}
