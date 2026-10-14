<?php

/**
 * Minimal fakes of the WordPress role and option APIs used by orchestra-core.
 */

declare(strict_types=1);

use Orchestra\Core\Tests\Fakes\WordPress;

class WP_Role
{
    /**
     * @param array<string, bool> $capabilities
     */
    public function __construct(public string $name, public array $capabilities)
    {
    }

    public function add_cap(string $cap, bool $grant = true): void
    {
        $this->capabilities[$cap] = $grant;
    }

    public function remove_cap(string $cap): void
    {
        unset($this->capabilities[$cap]);
    }

    public function has_cap(string $cap): bool
    {
        return !empty($this->capabilities[$cap]);
    }
}

function __(string $text, string $domain = 'default'): string
{
    return $text;
}

function get_role(string $role): ?WP_Role
{
    return WordPress::$roles[$role] ?? null;
}

/**
 * @param array<string, bool> $capabilities
 */
function add_role(string $role, string $displayName, array $capabilities = []): ?WP_Role
{
    if (isset(WordPress::$roles[$role])) {
        return null;
    }

    return WordPress::$roles[$role] = new WP_Role($role, $capabilities);
}

function remove_role(string $role): void
{
    unset(WordPress::$roles[$role]);
}

function get_option(string $option, mixed $default = false): mixed
{
    return array_key_exists($option, WordPress::$options) ? WordPress::$options[$option] : $default;
}

function update_option(string $option, mixed $value, ?bool $autoload = null): bool
{
    WordPress::$options[$option] = $value;

    return true;
}

function delete_option(string $option): bool
{
    unset(WordPress::$options[$option]);

    return true;
}
