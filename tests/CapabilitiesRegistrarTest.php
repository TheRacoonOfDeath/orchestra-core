<?php

namespace Orchestra\Core\Tests;

use Orchestra\Core\Capabilities;
use Orchestra\Core\CapabilitiesManager;
use Orchestra\Core\CapabilitiesRegistrar;
use PHPUnit\Framework\TestCase;

class CapabilitiesRegistrarTest extends TestCase
{
    /**
     * Mock WordPress functions for testing.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // If WordPress is loaded, we can run integration tests
        if (!function_exists('get_role')) {
            $this->markTestSkipped('WordPress functions not available');
        }
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(CapabilitiesRegistrar::class));
    }

    public function testRegisterMethodExists(): void
    {
        $this->assertTrue(method_exists(CapabilitiesRegistrar::class, 'register'));
    }

    public function testUnregisterMethodExists(): void
    {
        $this->assertTrue(method_exists(CapabilitiesRegistrar::class, 'unregister'));
    }

    public function testSyncMethodExists(): void
    {
        $this->assertTrue(method_exists(CapabilitiesRegistrar::class, 'sync'));
    }

    public function testCapabilitiesManagerCanHandleRoles(): void
    {
        $conductor_caps = CapabilitiesManager::conductorCapabilities();
        $this->assertNotEmpty($conductor_caps);

        $organizer_caps = CapabilitiesManager::organizerCapabilities();
        $this->assertNotEmpty($organizer_caps);

        $member_caps = CapabilitiesManager::memberCapabilities();
        $this->assertNotEmpty($member_caps);

        $admin_caps = CapabilitiesManager::administratorCapabilities();
        $this->assertNotEmpty($admin_caps);
    }

    public function testRoleConstantsAreCorrect(): void
    {
        $roles = ['conductor', 'organizer', 'member', 'administrator'];

        foreach ($roles as $role) {
            $caps = CapabilitiesManager::forRole($role);
            $this->assertNotEmpty($caps, "Role '{$role}' should have capabilities");
        }
    }
}
