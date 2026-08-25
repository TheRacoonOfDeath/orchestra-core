<?php

namespace Orchestra\Core\Tests;

use Orchestra\Core\Capabilities;
use Orchestra\Core\CapabilitiesManager;
use PHPUnit\Framework\TestCase;

class CapabilitiesTest extends TestCase
{
    public function testAllCapabilitiesAreDefined(): void
    {
        $all = Capabilities::all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('members', $all);
        $this->assertArrayHasKey('library', $all);
        $this->assertArrayHasKey('projects', $all);

        // Each domain should have capabilities
        $this->assertNotEmpty($all['members']);
        $this->assertNotEmpty($all['library']);
        $this->assertNotEmpty($all['projects']);
    }

    public function testMembersCapabilities(): void
    {
        $members = Capabilities::all()['members'];

        $this->assertContains(Capabilities::MEMBERS_PROFILE_READ, $members);
        $this->assertContains(Capabilities::MEMBERS_PROFILE_EDIT_OWN, $members);
        $this->assertContains(Capabilities::MEMBERS_PROFILE_EDIT_ANY, $members);
        $this->assertCount(3, $members);
    }

    public function testLibraryCapabilities(): void
    {
        $library = Capabilities::all()['library'];

        // Pieces
        $this->assertContains(Capabilities::LIBRARY_PIECE_READ, $library);
        $this->assertContains(Capabilities::LIBRARY_PIECE_CREATE, $library);
        $this->assertContains(Capabilities::LIBRARY_PIECE_EDIT_OWN, $library);
        $this->assertContains(Capabilities::LIBRARY_PIECE_EDIT_ANY, $library);
        $this->assertContains(Capabilities::LIBRARY_PIECE_DELETE_OWN, $library);
        $this->assertContains(Capabilities::LIBRARY_PIECE_DELETE_ANY, $library);

        // Parts
        $this->assertContains(Capabilities::LIBRARY_PART_READ, $library);
        $this->assertContains(Capabilities::LIBRARY_PART_CREATE, $library);
        $this->assertContains(Capabilities::LIBRARY_PART_EDIT_OWN, $library);
        $this->assertContains(Capabilities::LIBRARY_PART_EDIT_ANY, $library);
        $this->assertContains(Capabilities::LIBRARY_PART_DELETE_OWN, $library);
        $this->assertContains(Capabilities::LIBRARY_PART_DELETE_ANY, $library);

        // Sheet Music
        $this->assertContains(Capabilities::LIBRARY_SHEET_MUSIC_READ, $library);
        $this->assertContains(Capabilities::LIBRARY_SHEET_MUSIC_UPLOAD, $library);
        $this->assertContains(Capabilities::LIBRARY_SHEET_MUSIC_DELETE_OWN, $library);
        $this->assertContains(Capabilities::LIBRARY_SHEET_MUSIC_DELETE_ANY, $library);

        $this->assertCount(16, $library);
    }

    public function testProjectsCapabilities(): void
    {
        $projects = Capabilities::all()['projects'];

        // Projects
        $this->assertContains(Capabilities::PROJECTS_PROJECT_READ, $projects);
        $this->assertContains(Capabilities::PROJECTS_PROJECT_CREATE, $projects);
        $this->assertContains(Capabilities::PROJECTS_PROJECT_EDIT, $projects);
        $this->assertContains(Capabilities::PROJECTS_PROJECT_DELETE, $projects);

        // Participation
        $this->assertContains(Capabilities::PROJECTS_PARTICIPATION_READ, $projects);
        $this->assertContains(Capabilities::PROJECTS_PARTICIPATION_MANAGE_OWN, $projects);
        $this->assertContains(Capabilities::PROJECTS_PARTICIPATION_MANAGE_ANY, $projects);

        // Assignments
        $this->assertContains(Capabilities::PROJECTS_ASSIGNMENT_READ, $projects);
        $this->assertContains(Capabilities::PROJECTS_ASSIGNMENT_CREATE, $projects);
        $this->assertContains(Capabilities::PROJECTS_ASSIGNMENT_EDIT, $projects);
        $this->assertContains(Capabilities::PROJECTS_ASSIGNMENT_DELETE, $projects);

        // Communication & Roles
        $this->assertContains(Capabilities::PROJECTS_EMAIL_SEND, $projects);
        $this->assertContains(Capabilities::PROJECTS_CONDUCTOR_MANAGE, $projects);

        $this->assertCount(13, $projects);
    }

    public function testAdministratorHasAllCapabilities(): void
    {
        $admin_caps = CapabilitiesManager::administratorCapabilities();
        $all_caps = array_merge(...array_values(Capabilities::all()));

        $this->assertCount(count($all_caps), $admin_caps);
        foreach ($all_caps as $cap) {
            $this->assertContains($cap, $admin_caps);
        }
    }

    public function testOrganizerCapabilities(): void
    {
        $caps = CapabilitiesManager::organizerCapabilities();

        // Should have project management
        $this->assertContains(Capabilities::PROJECTS_PROJECT_READ, $caps);
        $this->assertContains(Capabilities::PROJECTS_PROJECT_CREATE, $caps);
        $this->assertContains(Capabilities::PROJECTS_PROJECT_EDIT, $caps);
        $this->assertContains(Capabilities::PROJECTS_PARTICIPATION_MANAGE_ANY, $caps);
        $this->assertContains(Capabilities::PROJECTS_EMAIL_SEND, $caps);
        $this->assertContains(Capabilities::PROJECTS_CONDUCTOR_MANAGE, $caps);

        // Should NOT have delete projects
        $this->assertNotContains(Capabilities::PROJECTS_PROJECT_DELETE, $caps);

        // Should have read-only library access
        $this->assertContains(Capabilities::LIBRARY_PIECE_READ, $caps);
        $this->assertContains(Capabilities::LIBRARY_PART_READ, $caps);
        $this->assertContains(Capabilities::LIBRARY_SHEET_MUSIC_READ, $caps);

        // Should NOT have create library
        $this->assertNotContains(Capabilities::LIBRARY_PIECE_CREATE, $caps);
        $this->assertNotContains(Capabilities::LIBRARY_PART_CREATE, $caps);
        $this->assertNotContains(Capabilities::LIBRARY_SHEET_MUSIC_UPLOAD, $caps);

        // Should have profile read
        $this->assertContains(Capabilities::MEMBERS_PROFILE_READ, $caps);
    }

    public function testConductorCapabilities(): void
    {
        $caps = CapabilitiesManager::conductorCapabilities();

        // Should have library management
        $this->assertContains(Capabilities::LIBRARY_PIECE_READ, $caps);
        $this->assertContains(Capabilities::LIBRARY_PIECE_CREATE, $caps);
        $this->assertContains(Capabilities::LIBRARY_PIECE_EDIT_ANY, $caps);
        $this->assertContains(Capabilities::LIBRARY_PART_CREATE, $caps);
        $this->assertContains(Capabilities::LIBRARY_SHEET_MUSIC_UPLOAD, $caps);

        // Should have assignment management
        $this->assertContains(Capabilities::PROJECTS_ASSIGNMENT_READ, $caps);
        $this->assertContains(Capabilities::PROJECTS_ASSIGNMENT_CREATE, $caps);
        $this->assertContains(Capabilities::PROJECTS_ASSIGNMENT_EDIT, $caps);

        // Should have email
        $this->assertContains(Capabilities::PROJECTS_EMAIL_SEND, $caps);

        // Should NOT have project creation
        $this->assertNotContains(Capabilities::PROJECTS_PROJECT_CREATE, $caps);
        $this->assertNotContains(Capabilities::PROJECTS_PROJECT_EDIT, $caps);

        // Should NOT have participation management
        $this->assertNotContains(Capabilities::PROJECTS_PARTICIPATION_MANAGE_ANY, $caps);

        // Should NOT have conductor assignment
        $this->assertNotContains(Capabilities::PROJECTS_CONDUCTOR_MANAGE, $caps);
    }

    public function testMemberCapabilities(): void
    {
        $caps = CapabilitiesManager::memberCapabilities();

        // Should have read-only library
        $this->assertContains(Capabilities::LIBRARY_PIECE_READ, $caps);
        $this->assertContains(Capabilities::LIBRARY_PART_READ, $caps);
        $this->assertContains(Capabilities::LIBRARY_SHEET_MUSIC_READ, $caps);

        // Should NOT have create library
        $this->assertNotContains(Capabilities::LIBRARY_PIECE_CREATE, $caps);
        $this->assertNotContains(Capabilities::LIBRARY_SHEET_MUSIC_UPLOAD, $caps);

        // Should have project read and own participation management
        $this->assertContains(Capabilities::PROJECTS_PROJECT_READ, $caps);
        $this->assertContains(Capabilities::PROJECTS_PARTICIPATION_MANAGE_OWN, $caps);

        // Should NOT have any participation management
        $this->assertNotContains(Capabilities::PROJECTS_PARTICIPATION_MANAGE_ANY, $caps);

        // Should NOT have project creation
        $this->assertNotContains(Capabilities::PROJECTS_PROJECT_CREATE, $caps);
        $this->assertNotContains(Capabilities::PROJECTS_ASSIGNMENT_CREATE, $caps);

        // Should have own profile edit
        $this->assertContains(Capabilities::MEMBERS_PROFILE_EDIT_OWN, $caps);

        // Should NOT have any profile edit
        $this->assertNotContains(Capabilities::MEMBERS_PROFILE_EDIT_ANY, $caps);
    }

    public function testForRoleMethod(): void
    {
        $this->assertNotEmpty(CapabilitiesManager::forRole('administrator'));
        $this->assertNotEmpty(CapabilitiesManager::forRole('organizer'));
        $this->assertNotEmpty(CapabilitiesManager::forRole('conductor'));
        $this->assertNotEmpty(CapabilitiesManager::forRole('member'));

        // Invalid role should return empty array
        $this->assertEmpty(CapabilitiesManager::forRole('invalid_role'));
    }

    public function testRequiresOwnershipCheck(): void
    {
        // Own capabilities should require check
        $this->assertTrue(Capabilities::requiresOwnershipCheck(Capabilities::LIBRARY_PIECE_EDIT_OWN));
        $this->assertTrue(Capabilities::requiresOwnershipCheck(Capabilities::MEMBERS_PROFILE_EDIT_OWN));
        $this->assertTrue(Capabilities::requiresOwnershipCheck(Capabilities::PROJECTS_PARTICIPATION_MANAGE_OWN));

        // Any capabilities should not require check
        $this->assertFalse(Capabilities::requiresOwnershipCheck(Capabilities::LIBRARY_PIECE_EDIT_ANY));
        $this->assertFalse(Capabilities::requiresOwnershipCheck(Capabilities::MEMBERS_PROFILE_EDIT_ANY));
        $this->assertFalse(Capabilities::requiresOwnershipCheck(Capabilities::PROJECTS_PARTICIPATION_MANAGE_ANY));

        // Non-ownership capabilities should not require check
        $this->assertFalse(Capabilities::requiresOwnershipCheck(Capabilities::LIBRARY_PIECE_READ));
        $this->assertFalse(Capabilities::requiresOwnershipCheck(Capabilities::LIBRARY_PIECE_CREATE));
    }

    public function testGetAnyCapability(): void
    {
        $this->assertEquals(
            Capabilities::LIBRARY_PIECE_EDIT_ANY,
            Capabilities::getAnyCapability(Capabilities::LIBRARY_PIECE_EDIT_OWN)
        );

        $this->assertEquals(
            Capabilities::MEMBERS_PROFILE_EDIT_ANY,
            Capabilities::getAnyCapability(Capabilities::MEMBERS_PROFILE_EDIT_OWN)
        );

        // Non-own capability should return null
        $this->assertNull(Capabilities::getAnyCapability(Capabilities::LIBRARY_PIECE_READ));
        $this->assertNull(Capabilities::getAnyCapability(Capabilities::LIBRARY_PIECE_EDIT_ANY));
    }

    public function testGetOwnCapability(): void
    {
        $this->assertEquals(
            Capabilities::LIBRARY_PIECE_EDIT_OWN,
            Capabilities::getOwnCapability(Capabilities::LIBRARY_PIECE_EDIT_ANY)
        );

        $this->assertEquals(
            Capabilities::MEMBERS_PROFILE_EDIT_OWN,
            Capabilities::getOwnCapability(Capabilities::MEMBERS_PROFILE_EDIT_ANY)
        );

        // Non-any capability should return null
        $this->assertNull(Capabilities::getOwnCapability(Capabilities::LIBRARY_PIECE_READ));
        $this->assertNull(Capabilities::getOwnCapability(Capabilities::LIBRARY_PIECE_EDIT_OWN));
    }

    public function testCapabilityNamingConsistency(): void
    {
        $all_caps = array_merge(...array_values(Capabilities::all()));

        foreach ($all_caps as $cap) {
            // All capabilities should start with 'orchestra_'
            $this->assertStringStartsWith('orchestra_', $cap);

            // All capabilities should be lowercase with underscores
            $this->assertEquals($cap, strtolower($cap));
            $this->assertStringNotContainsString('-', $cap);
        }
    }

    public function testNoCapabilityDuplicates(): void
    {
        $all_caps = array_merge(...array_values(Capabilities::all()));
        $unique_caps = array_unique($all_caps);

        $this->assertCount(count($all_caps), $unique_caps, 'Duplicate capabilities found');
    }

    public function testAdministratorHasOrganizerCapabilities(): void
    {
        $admin_caps = CapabilitiesManager::administratorCapabilities();
        $organizer_caps = CapabilitiesManager::organizerCapabilities();

        foreach ($organizer_caps as $cap) {
            $this->assertContains($cap, $admin_caps, "Admin should have organizer capability: $cap");
        }
    }

    public function testAdministratorHasConductorCapabilities(): void
    {
        $admin_caps = CapabilitiesManager::administratorCapabilities();
        $conductor_caps = CapabilitiesManager::conductorCapabilities();

        foreach ($conductor_caps as $cap) {
            $this->assertContains($cap, $admin_caps, "Admin should have conductor capability: $cap");
        }
    }

    public function testAdministratorHasMemberCapabilities(): void
    {
        $admin_caps = CapabilitiesManager::administratorCapabilities();
        $member_caps = CapabilitiesManager::memberCapabilities();

        foreach ($member_caps as $cap) {
            $this->assertContains($cap, $admin_caps, "Admin should have member capability: $cap");
        }
    }
}

