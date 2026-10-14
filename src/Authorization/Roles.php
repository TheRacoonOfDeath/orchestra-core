<?php

declare(strict_types=1);

namespace Orchestra\Core\Authorization;

/**
 * The roles shared by all Orchestra plugins.
 *
 * Orchestra core owns the roles; each plugin decides which of its capabilities a role receives.
 */
final class Roles
{
    public const MEMBER = 'orchestra_member';
    public const CONDUCTOR = 'orchestra_conductor';
    public const ORGANIZER = 'orchestra_organizer';

    /**
     * The WordPress administrator role. It implicitly receives every registered capability.
     */
    public const ADMINISTRATOR = 'administrator';

    /**
     * Roles created by Orchestra, to which plugins may grant capabilities.
     */
    public const GRANTABLE = [self::MEMBER, self::CONDUCTOR, self::ORGANIZER];

    /**
     * @return array<string, string> role name => translated display name
     */
    public static function displayNames(): array
    {
        return [
            self::MEMBER => \__('Orchestra Member', 'orchestra-core'),
            self::CONDUCTOR => \__('Orchestra Conductor', 'orchestra-core'),
            self::ORGANIZER => \__('Orchestra Organizer', 'orchestra-core'),
        ];
    }
}
