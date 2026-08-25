<?php

namespace Orchestra\Core;

class CapabilitiesRegistrar
{
    /**
     * Register all Orchestra roles and assign capabilities.
     *
     * Should be called on plugin activation.
     *
     * @return void
     */
    public static function register(): void
    {
        self::createRoles();
        self::assignCapabilities();
    }

    /**
     * Unregister Orchestra roles and remove capabilities.
     *
     * Should be called on plugin deactivation/uninstall.
     *
     * @return void
     */
    public static function unregister(): void
    {
        self::removeCapabilities();
        self::removeRoles();
    }

    /**
     * Create Orchestra roles if they don't exist.
     *
     * @return void
     */
    private static function createRoles(): void
    {
        // Conductor role
        if (!get_role('conductor')) {
            add_role(
                'conductor',
                'Conductor',
                [] // Capabilities are assigned separately
            );
        }

        // Organizer role
        if (!get_role('organizer')) {
            add_role(
                'organizer',
                'Organizer',
                [] // Capabilities are assigned separately
            );
        }

        // Note: 'member' role typically doesn't need to be created as it's handled by
        // mapping users to the standard WordPress 'subscriber' role or similar
    }

    /**
     * Assign capabilities to Orchestra roles.
     *
     * @return void
     */
    private static function assignCapabilities(): void
    {
        CapabilitiesManager::assignToRole('conductor', 'conductor');
        CapabilitiesManager::assignToRole('organizer', 'organizer');

        // Members use the default subscriber role with member capabilities
        CapabilitiesManager::assignToRole('subscriber', 'member');

        // Ensure administrators have all capabilities
        CapabilitiesManager::assignToRole('administrator', 'administrator');
    }

    /**
     * Remove all Orchestra capabilities from roles.
     *
     * @return void
     */
    private static function removeCapabilities(): void
    {
        CapabilitiesManager::removeFromRole('conductor');
        CapabilitiesManager::removeFromRole('organizer');
        CapabilitiesManager::removeFromRole('subscriber');
        CapabilitiesManager::removeFromRole('administrator');
    }

    /**
     * Remove Orchestra roles.
     *
     * Note: We don't remove administrator role as it's a core WordPress role.
     *
     * @return void
     */
    private static function removeRoles(): void
    {
        remove_role('conductor');
        remove_role('organizer');
        // subscriber role is a core WordPress role, don't remove it
    }

    /**
     * Update/sync capabilities for existing roles.
     *
     * Useful for plugin updates where capability definitions may have changed.
     *
     * @return void
     */
    public static function sync(): void
    {
        self::assignCapabilities();
    }
}
