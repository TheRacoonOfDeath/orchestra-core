<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

use Orchestra\Core\Authorization\Roles;
use Orchestra\Core\Tests\Fakes\WordPress;
use Orchestra\Core\Ui\AdminAccess;
use PHPUnit\Framework\TestCase;
use WP_User;

final class AdminAccessTest extends TestCase
{
    protected function setUp(): void
    {
        WordPress::reset();
    }

    public function testKeepsOrchestraRolesOutOfTheDashboard(): void
    {
        $this->assertTrue(AdminAccess::isRestricted(self::user([Roles::MEMBER])));
        $this->assertTrue(AdminAccess::isRestricted(self::user([Roles::CONDUCTOR, Roles::ORGANIZER])));
        $this->assertTrue(AdminAccess::isRestricted(self::user(['subscriber', Roles::ORGANIZER])));
    }

    public function testLeavesOtherUsersAlone(): void
    {
        $this->assertFalse(AdminAccess::isRestricted(self::user([Roles::ADMINISTRATOR])));
        $this->assertFalse(AdminAccess::isRestricted(self::user([Roles::ADMINISTRATOR, Roles::ORGANIZER])));
        $this->assertFalse(AdminAccess::isRestricted(self::user(['editor', Roles::MEMBER])));
        $this->assertFalse(AdminAccess::isRestricted(self::user(['subscriber'])));
        $this->assertFalse(AdminAccess::isRestricted(self::user([])));
    }

    public function testCanBeTurnedOffWithAFilter(): void
    {
        WordPress::$filters[AdminAccess::FILTER] = static fn (bool $restricted): bool => false;

        $this->assertFalse(AdminAccess::isRestricted(self::user([Roles::MEMBER])));
    }

    /**
     * @param list<string> $roles
     */
    private static function user(array $roles): WP_User
    {
        $user = new WP_User();
        $user->ID = 7;
        $user->roles = $roles;

        return $user;
    }
}
