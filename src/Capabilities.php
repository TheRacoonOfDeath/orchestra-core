<?php

namespace Orchestra\Core;

class Capabilities
{
    // ======== MEMBERS DOMAIN ========
    public const MEMBERS_PROFILE_READ = 'orchestra_members_profile_read';
    public const MEMBERS_PROFILE_EDIT_OWN = 'orchestra_members_profile_edit_own';
    public const MEMBERS_PROFILE_EDIT_ANY = 'orchestra_members_profile_edit_any';

    // ======== LIBRARY DOMAIN ========
    // Pieces
    public const LIBRARY_PIECE_READ = 'orchestra_library_piece_read';
    public const LIBRARY_PIECE_CREATE = 'orchestra_library_piece_create';
    public const LIBRARY_PIECE_EDIT_OWN = 'orchestra_library_piece_edit_own';
    public const LIBRARY_PIECE_EDIT_ANY = 'orchestra_library_piece_edit_any';
    public const LIBRARY_PIECE_DELETE_OWN = 'orchestra_library_piece_delete_own';
    public const LIBRARY_PIECE_DELETE_ANY = 'orchestra_library_piece_delete_any';

    // Parts
    public const LIBRARY_PART_READ = 'orchestra_library_part_read';
    public const LIBRARY_PART_CREATE = 'orchestra_library_part_create';
    public const LIBRARY_PART_EDIT_OWN = 'orchestra_library_part_edit_own';
    public const LIBRARY_PART_EDIT_ANY = 'orchestra_library_part_edit_any';
    public const LIBRARY_PART_DELETE_OWN = 'orchestra_library_part_delete_own';
    public const LIBRARY_PART_DELETE_ANY = 'orchestra_library_part_delete_any';

    // Sheet Music
    public const LIBRARY_SHEET_MUSIC_READ = 'orchestra_library_sheet_music_read';
    public const LIBRARY_SHEET_MUSIC_UPLOAD = 'orchestra_library_sheet_music_upload';
    public const LIBRARY_SHEET_MUSIC_DELETE_OWN = 'orchestra_library_sheet_music_delete_own';
    public const LIBRARY_SHEET_MUSIC_DELETE_ANY = 'orchestra_library_sheet_music_delete_any';

    // ======== PROJECTS DOMAIN ========
    // Projects
    public const PROJECTS_PROJECT_READ = 'orchestra_projects_project_read';
    public const PROJECTS_PROJECT_CREATE = 'orchestra_projects_project_create';
    public const PROJECTS_PROJECT_EDIT = 'orchestra_projects_project_edit';
    public const PROJECTS_PROJECT_DELETE = 'orchestra_projects_project_delete';

    // Participation
    public const PROJECTS_PARTICIPATION_READ = 'orchestra_projects_participation_read';
    public const PROJECTS_PARTICIPATION_MANAGE_OWN = 'orchestra_projects_participation_manage_own';
    public const PROJECTS_PARTICIPATION_MANAGE_ANY = 'orchestra_projects_participation_manage_any';

    // Assignments
    public const PROJECTS_ASSIGNMENT_READ = 'orchestra_projects_assignment_read';
    public const PROJECTS_ASSIGNMENT_CREATE = 'orchestra_projects_assignment_create';
    public const PROJECTS_ASSIGNMENT_EDIT = 'orchestra_projects_assignment_edit';
    public const PROJECTS_ASSIGNMENT_DELETE = 'orchestra_projects_assignment_delete';

    // Communication
    public const PROJECTS_EMAIL_SEND = 'orchestra_projects_email_send';

    // ======== CONDUCTOR ROLES ========
    public const PROJECTS_CONDUCTOR_MANAGE = 'orchestra_projects_conductor_manage';

    /**
     * All available capabilities grouped by domain.
     *
     * @return array<string, array<string>>
     */
    public static function all(): array
    {
        return [
            'members' => [
                self::MEMBERS_PROFILE_READ,
                self::MEMBERS_PROFILE_EDIT_OWN,
                self::MEMBERS_PROFILE_EDIT_ANY,
            ],
            'library' => [
                self::LIBRARY_PIECE_READ,
                self::LIBRARY_PIECE_CREATE,
                self::LIBRARY_PIECE_EDIT_OWN,
                self::LIBRARY_PIECE_EDIT_ANY,
                self::LIBRARY_PIECE_DELETE_OWN,
                self::LIBRARY_PIECE_DELETE_ANY,
                self::LIBRARY_PART_READ,
                self::LIBRARY_PART_CREATE,
                self::LIBRARY_PART_EDIT_OWN,
                self::LIBRARY_PART_EDIT_ANY,
                self::LIBRARY_PART_DELETE_OWN,
                self::LIBRARY_PART_DELETE_ANY,
                self::LIBRARY_SHEET_MUSIC_READ,
                self::LIBRARY_SHEET_MUSIC_UPLOAD,
                self::LIBRARY_SHEET_MUSIC_DELETE_OWN,
                self::LIBRARY_SHEET_MUSIC_DELETE_ANY,
            ],
            'projects' => [
                self::PROJECTS_PROJECT_READ,
                self::PROJECTS_PROJECT_CREATE,
                self::PROJECTS_PROJECT_EDIT,
                self::PROJECTS_PROJECT_DELETE,
                self::PROJECTS_PARTICIPATION_READ,
                self::PROJECTS_PARTICIPATION_MANAGE_OWN,
                self::PROJECTS_PARTICIPATION_MANAGE_ANY,
                self::PROJECTS_ASSIGNMENT_READ,
                self::PROJECTS_ASSIGNMENT_CREATE,
                self::PROJECTS_ASSIGNMENT_EDIT,
                self::PROJECTS_ASSIGNMENT_DELETE,
                self::PROJECTS_EMAIL_SEND,
                self::PROJECTS_CONDUCTOR_MANAGE,
            ],
        ];
    }


    /**
     * Check if a capability requires resource ownership check.
     *
     * @param string $capability
     * @return bool
     */
    public static function requiresOwnershipCheck(string $capability): bool
    {
        return str_contains($capability, '_own');
    }

    /**
     * Get the "any" version of an "own" capability.
     *
     * Example: orchestra_members_profile_edit_own → orchestra_members_profile_edit_any
     *
     * @param string $ownCapability
     * @return string|null
     */
    public static function getAnyCapability(string $ownCapability): ?string
    {
        if (!str_ends_with($ownCapability, '_own')) {
            return null;
        }

        return str_replace('_own', '_any', $ownCapability);
    }

    /**
     * Get the "own" version of an "any" capability.
     *
     * Example: orchestra_members_profile_edit_any → orchestra_members_profile_edit_own
     *
     * @param string $anyCapability
     * @return string|null
     */
    public static function getOwnCapability(string $anyCapability): ?string
    {
        if (!str_ends_with($anyCapability, '_any')) {
            return null;
        }

        return str_replace('_any', '_own', $anyCapability);
    }
}
