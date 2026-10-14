<?php

declare(strict_types=1);

namespace Orchestra\Core\Authorization;

/**
 * Writes the registered capabilities onto the WordPress roles.
 *
 * Roles are stored in the database, so they only need to be updated when the registered
 * capabilities change, e.g. after a plugin update or when an Orchestra plugin is (de)activated.
 * Capabilities that Orchestra granted earlier but that are no longer registered are removed.
 */
final class CapabilitySynchronizer
{
    public const OPTION_FINGERPRINT = 'orchestra_core_capabilities_fingerprint';
    public const OPTION_MANAGED_CAPABILITIES = 'orchestra_core_managed_capabilities';

    public function __construct(private readonly CapabilityRegistry $registry)
    {
    }

    public function syncIfOutdated(): void
    {
        if ($this->rolesExist() && \get_option(self::OPTION_FINGERPRINT) === $this->registry->fingerprint()) {
            return;
        }

        $this->sync();
    }

    public function sync(): void
    {
        $this->createMissingRoles();

        $desired = $this->registry->capabilitiesByRole();
        $previous = self::managedCapabilities();

        foreach (\array_unique([...\array_keys($desired), ...\array_keys($previous)]) as $roleName) {
            $role = \get_role($roleName);
            if ($role === null) {
                continue;
            }

            foreach (\array_diff($previous[$roleName] ?? [], $desired[$roleName] ?? []) as $capability) {
                $role->remove_cap($capability);
            }
            foreach ($desired[$roleName] ?? [] as $capability) {
                $role->add_cap($capability);
            }
        }

        \update_option(self::OPTION_MANAGED_CAPABILITIES, $desired, false);
        \update_option(self::OPTION_FINGERPRINT, $this->registry->fingerprint(), true);
    }

    /**
     * Removes all Orchestra roles and every capability Orchestra granted. Only for uninstalling.
     */
    public static function removeAll(): void
    {
        foreach (self::managedCapabilities() as $roleName => $capabilities) {
            $role = \get_role($roleName);
            if ($role === null) {
                continue;
            }

            foreach ($capabilities as $capability) {
                $role->remove_cap($capability);
            }
        }

        foreach (Roles::GRANTABLE as $roleName) {
            \remove_role($roleName);
        }

        \delete_option(self::OPTION_MANAGED_CAPABILITIES);
        \delete_option(self::OPTION_FINGERPRINT);
    }

    private function rolesExist(): bool
    {
        foreach (Roles::GRANTABLE as $roleName) {
            if (\get_role($roleName) === null) {
                return false;
            }
        }

        return true;
    }

    private function createMissingRoles(): void
    {
        foreach (Roles::displayNames() as $roleName => $displayName) {
            if (\get_role($roleName) === null) {
                \add_role($roleName, $displayName, ['read' => true]);
            }
        }
    }

    /**
     * @return array<string, list<string>> role => capabilities granted during the last sync
     */
    private static function managedCapabilities(): array
    {
        $stored = \get_option(self::OPTION_MANAGED_CAPABILITIES, []);
        if (!\is_array($stored)) {
            return [];
        }

        $managed = [];
        foreach ($stored as $roleName => $capabilities) {
            if (\is_string($roleName) && \is_array($capabilities)) {
                $managed[$roleName] = \array_values(\array_filter($capabilities, \is_string(...)));
            }
        }

        return $managed;
    }
}
