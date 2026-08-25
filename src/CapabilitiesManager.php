<?php

namespace Orchestra\Core;

class CapabilitiesManager
{
    /**
     * Get all capabilities for a specific role.
     *
     * @param string $role Role name (administrator, organizer, conductor, member)
     * @return array<string>
     */
    public static function forRole(string $role): array
    {
        return match ($role) {
            'administrator' => self::administratorCapabilities(),
            'organizer' => self::organizerCapabilities(),
            'conductor' => self::conductorCapabilities(),
            'member' => self::memberCapabilities(),
            default => [],
        };
    }

    /**
     * Get capabilities for the Administrator role (all capabilities).
     *
     * @return array<string>
     */
    public static function administratorCapabilities(): array
    {
        $all = Capabilities::all();
        return array_merge(...array_values($all));
    }

    /**
     * Get capabilities for the Organizer role.
     *
     * @return array<string>
     */
    public static function organizerCapabilities(): array
    {
        return [
            // Projects
            Capabilities::PROJECTS_PROJECT_READ,
            Capabilities::PROJECTS_PROJECT_CREATE,
            Capabilities::PROJECTS_PROJECT_EDIT,
            Capabilities::PROJECTS_PARTICIPATION_READ,
            Capabilities::PROJECTS_PARTICIPATION_MANAGE_ANY,
            Capabilities::PROJECTS_EMAIL_SEND,
            Capabilities::PROJECTS_CONDUCTOR_MANAGE,

            // Members (read-only)
            Capabilities::MEMBERS_PROFILE_READ,

            // Library (read-only)
            Capabilities::LIBRARY_PIECE_READ,
            Capabilities::LIBRARY_PART_READ,
            Capabilities::LIBRARY_SHEET_MUSIC_READ,
        ];
    }

    /**
     * Get capabilities for the Conductor role.
     *
     * @return array<string>
     */
    public static function conductorCapabilities(): array
    {
        return [
            // Library (full for pieces and parts, upload for sheet music)
            Capabilities::LIBRARY_PIECE_READ,
            Capabilities::LIBRARY_PIECE_CREATE,
            Capabilities::LIBRARY_PIECE_EDIT_ANY,
            Capabilities::LIBRARY_PIECE_DELETE_ANY,
            Capabilities::LIBRARY_PART_READ,
            Capabilities::LIBRARY_PART_CREATE,
            Capabilities::LIBRARY_PART_EDIT_ANY,
            Capabilities::LIBRARY_PART_DELETE_ANY,
            Capabilities::LIBRARY_SHEET_MUSIC_READ,
            Capabilities::LIBRARY_SHEET_MUSIC_UPLOAD,

            // Projects (assignments and communication)
            Capabilities::PROJECTS_PROJECT_READ,
            Capabilities::PROJECTS_PARTICIPATION_READ,
            Capabilities::PROJECTS_ASSIGNMENT_READ,
            Capabilities::PROJECTS_ASSIGNMENT_CREATE,
            Capabilities::PROJECTS_ASSIGNMENT_EDIT,
            Capabilities::PROJECTS_ASSIGNMENT_DELETE,
            Capabilities::PROJECTS_EMAIL_SEND,
        ];
    }

    /**
     * Get capabilities for the Member role.
     *
     * @return array<string>
     */
    public static function memberCapabilities(): array
    {
        return [
            // Library (read-only, download sheet music)
            Capabilities::LIBRARY_PIECE_READ,
            Capabilities::LIBRARY_PART_READ,
            Capabilities::LIBRARY_SHEET_MUSIC_READ,

            // Projects (read and manage own participation)
            Capabilities::PROJECTS_PROJECT_READ,
            Capabilities::PROJECTS_PARTICIPATION_READ,
            Capabilities::PROJECTS_PARTICIPATION_MANAGE_OWN,
            Capabilities::PROJECTS_ASSIGNMENT_READ,

            // Members (read and edit own profile)
            Capabilities::MEMBERS_PROFILE_READ,
            Capabilities::MEMBERS_PROFILE_EDIT_OWN,
        ];
    }

    /**
     * Assign capabilities to a WordPress role.
     *
     * @param string $role_name WordPress role name (e.g., 'organizer', 'conductor')
     * @param string $orchestra_role Orchestra role constant (e.g., 'organizer')
     * @return bool True if successful, false if role doesn't exist
     */
    public static function assignToRole(string $role_name, string $orchestra_role): bool
    {
        $wp_role = get_role($role_name);
        if (!$wp_role) {
            return false;
        }

        $capabilities = self::forRole($orchestra_role);
        foreach ($capabilities as $cap) {
            $wp_role->add_cap($cap);
        }

        return true;
    }

    /**
     * Remove capabilities from a WordPress role.
     *
     * @param string $role_name WordPress role name
     * @return void
     */
    public static function removeFromRole(string $role_name): void
    {
        $wp_role = get_role($role_name);
        if (!$wp_role) {
            return;
        }

        $all_capabilities = Capabilities::all();
        $all_caps = array_merge(...array_values($all_capabilities));

        foreach ($all_caps as $cap) {
            $wp_role->remove_cap($cap);
        }
    }

    /**
     * Sync capabilities for a role (remove old, add new).
     *
     * @param string $role_name WordPress role name
     * @param string $orchestra_role Orchestra role constant
     * @return bool True if successful
     */
    public static function syncRole(string $role_name, string $orchestra_role): bool
    {
        self::removeFromRole($role_name);
        return self::assignToRole($role_name, $orchestra_role);
    }

    /**
     * Check if a user has a specific capability, respecting ownership rules.
     *
     * For capabilities ending in '_own', additionally checks if the user owns the resource.
     *
     * @param int $user_id
     * @param string $capability
     * @param int|null $post_id If capability requires ownership check, pass the post ID
     * @return bool
     */
    public static function userCan(int $user_id, string $capability, ?int $post_id = null): bool
    {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }

        // Check if user has the capability
        if (!user_can($user_id, $capability)) {
            return false;
        }

        // If this is an '_own' capability, verify ownership
        if (Capabilities::requiresOwnershipCheck($capability) && $post_id) {
            $post = get_post($post_id);
            if (!$post || (int)$post->post_author !== (int)$user_id) {
                return false;
            }
        }

        return true;
    }
}
