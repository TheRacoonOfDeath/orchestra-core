<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Authorization;

use Orchestra\Core\Authorization\CapabilityRegistry;
use Orchestra\Core\Authorization\CapabilitySynchronizer;
use Orchestra\Core\Authorization\Roles;
use Orchestra\Core\Tests\Fakes\WordPress;
use PHPUnit\Framework\TestCase;

final class CapabilitySynchronizerTest extends TestCase
{
    protected function setUp(): void
    {
        WordPress::reset();
        \add_role(Roles::ADMINISTRATOR, 'Administrator', ['manage_options' => true]);
    }

    public function testCreatesOrchestraRolesThatCanRead(): void
    {
        (new CapabilitySynchronizer(new CapabilityRegistry()))->sync();

        foreach (Roles::GRANTABLE as $roleName) {
            $role = \get_role($roleName);
            $this->assertNotNull($role, "Role {$roleName} should exist");
            $this->assertTrue($role->has_cap('read'));
        }
    }

    public function testAssignsRegisteredCapabilities(): void
    {
        $this->synchronizer(['orchestra_library_piece_create' => [Roles::CONDUCTOR]])->sync();

        $this->assertTrue($this->role(Roles::CONDUCTOR)->has_cap('orchestra_library_piece_create'));
        $this->assertFalse($this->role(Roles::MEMBER)->has_cap('orchestra_library_piece_create'));
        $this->assertTrue($this->role(Roles::ADMINISTRATOR)->has_cap('orchestra_library_piece_create'));
    }

    public function testRemovesCapabilitiesThatAreNoLongerGranted(): void
    {
        $this->synchronizer([
            'orchestra_library_piece_create' => [Roles::CONDUCTOR],
            'orchestra_library_piece_read' => [Roles::MEMBER, Roles::CONDUCTOR],
        ])->sync();

        $this->synchronizer(['orchestra_library_piece_read' => [Roles::CONDUCTOR]])->sync();

        $this->assertFalse($this->role(Roles::CONDUCTOR)->has_cap('orchestra_library_piece_create'));
        $this->assertFalse($this->role(Roles::ADMINISTRATOR)->has_cap('orchestra_library_piece_create'));
        $this->assertFalse($this->role(Roles::MEMBER)->has_cap('orchestra_library_piece_read'));
        $this->assertTrue($this->role(Roles::CONDUCTOR)->has_cap('orchestra_library_piece_read'));
    }

    public function testKeepsCapabilitiesItDidNotGrant(): void
    {
        \add_role(Roles::CONDUCTOR, 'Conductor', ['read' => true, 'upload_files' => true]);

        $this->synchronizer(['orchestra_library_piece_create' => [Roles::CONDUCTOR]])->sync();
        $this->synchronizer([])->sync();

        $this->assertTrue($this->role(Roles::CONDUCTOR)->has_cap('upload_files'));
        $this->assertTrue($this->role(Roles::ADMINISTRATOR)->has_cap('manage_options'));
    }

    public function testSkipsSyncWhileNothingChanged(): void
    {
        $grants = ['orchestra_library_piece_create' => [Roles::CONDUCTOR]];
        $this->synchronizer($grants)->sync();
        // Simulates an administrator deliberately revoking a capability.
        $this->role(Roles::CONDUCTOR)->remove_cap('orchestra_library_piece_create');

        $this->synchronizer($grants)->syncIfOutdated();

        $this->assertFalse($this->role(Roles::CONDUCTOR)->has_cap('orchestra_library_piece_create'));
    }

    public function testSyncsWhenRegisteredCapabilitiesChanged(): void
    {
        $this->synchronizer(['orchestra_library_piece_read' => [Roles::MEMBER]])->sync();

        $this->synchronizer(['orchestra_library_piece_read' => [Roles::MEMBER, Roles::ORGANIZER]])->syncIfOutdated();

        $this->assertTrue($this->role(Roles::ORGANIZER)->has_cap('orchestra_library_piece_read'));
    }

    public function testSyncsWhenARoleIsMissing(): void
    {
        $grants = ['orchestra_library_piece_read' => [Roles::MEMBER]];
        $this->synchronizer($grants)->sync();
        \remove_role(Roles::MEMBER);

        $this->synchronizer($grants)->syncIfOutdated();

        $this->assertTrue($this->role(Roles::MEMBER)->has_cap('orchestra_library_piece_read'));
    }

    public function testToleratesCorruptStoredState(): void
    {
        WordPress::$options[CapabilitySynchronizer::OPTION_MANAGED_CAPABILITIES] = 'garbage';

        $this->synchronizer(['orchestra_library_piece_read' => [Roles::MEMBER]])->sync();

        $this->assertTrue($this->role(Roles::MEMBER)->has_cap('orchestra_library_piece_read'));
    }

    public function testRemoveAllDeletesRolesCapabilitiesAndOptions(): void
    {
        $this->synchronizer(['orchestra_library_piece_read' => [Roles::MEMBER]])->sync();

        CapabilitySynchronizer::removeAll();

        foreach (Roles::GRANTABLE as $roleName) {
            $this->assertNull(\get_role($roleName));
        }
        $this->assertFalse($this->role(Roles::ADMINISTRATOR)->has_cap('orchestra_library_piece_read'));
        $this->assertTrue($this->role(Roles::ADMINISTRATOR)->has_cap('manage_options'));
        $this->assertSame([], WordPress::$options);
    }

    /**
     * @param array<string, list<string>> $grants capability => roles
     */
    private function synchronizer(array $grants): CapabilitySynchronizer
    {
        $registry = new CapabilityRegistry();
        foreach ($grants as $capability => $roles) {
            $registry->register($capability, $roles);
        }

        return new CapabilitySynchronizer($registry);
    }

    private function role(string $name): \WP_Role
    {
        $role = \get_role($name);
        $this->assertNotNull($role, "Role {$name} should exist");

        return $role;
    }
}
