<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Authorization;

use InvalidArgumentException;
use LogicException;
use Orchestra\Core\Authorization\CapabilityRegistry;
use Orchestra\Core\Authorization\Roles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CapabilityRegistryTest extends TestCase
{
    public function testGrantsCapabilitiesToRolesAndAdministrator(): void
    {
        $registry = (new CapabilityRegistry())
            ->register('orchestra_library_piece_create', [Roles::CONDUCTOR])
            ->register('orchestra_library_piece_read', [Roles::MEMBER, Roles::CONDUCTOR]);

        $this->assertSame([
            Roles::MEMBER => ['orchestra_library_piece_read'],
            Roles::CONDUCTOR => ['orchestra_library_piece_create', 'orchestra_library_piece_read'],
            Roles::ORGANIZER => [],
            Roles::ADMINISTRATOR => ['orchestra_library_piece_create', 'orchestra_library_piece_read'],
        ], $registry->capabilitiesByRole());
    }

    public function testCapabilityWithoutRolesIsAdministratorOnly(): void
    {
        $registry = (new CapabilityRegistry())->register('orchestra_library_piece_delete');

        $byRole = $registry->capabilitiesByRole();
        $this->assertSame(['orchestra_library_piece_delete'], $byRole[Roles::ADMINISTRATOR]);
        $this->assertSame([], $byRole[Roles::CONDUCTOR]);
    }

    public function testRejectsUnknownRole(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CapabilityRegistry())->register('orchestra_library_piece_read', ['subscriber']);
    }

    public function testAdministratorCannotBeGrantedExplicitly(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CapabilityRegistry())->register('orchestra_library_piece_read', [Roles::ADMINISTRATOR]);
    }

    #[DataProvider('invalidNames')]
    public function testRejectsInvalidNames(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CapabilityRegistry())->register($name);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNames(): iterable
    {
        yield 'missing prefix' => ['library_piece_read'];
        yield 'prefix only' => ['orchestra_'];
        yield 'domain only' => ['orchestra_library'];
        yield 'uppercase' => ['orchestra_Library_piece_read'];
        yield 'dash' => ['orchestra_library_sheet-music_read'];
        yield 'trailing underscore' => ['orchestra_library_piece_'];
    }

    public function testRejectsDuplicateCapability(): void
    {
        $registry = (new CapabilityRegistry())->register('orchestra_library_piece_read', [Roles::MEMBER]);

        $this->expectException(LogicException::class);
        $registry->register('orchestra_library_piece_read', [Roles::CONDUCTOR]);
    }

    public function testRejectsMetaCapabilityWithNameOfPrimitiveCapability(): void
    {
        $registry = (new CapabilityRegistry())->register('orchestra_members_profile_edit');

        $this->expectException(LogicException::class);
        $registry->registerMetaCapability('orchestra_members_profile_edit', static fn (): array => []);
    }

    public function testRejectsRegistrationAfterSealing(): void
    {
        $registry = new CapabilityRegistry();
        $registry->seal();

        $this->expectException(LogicException::class);
        $registry->register('orchestra_library_piece_read');
    }

    public function testReturnsRegisteredMetaCapabilityMapper(): void
    {
        $mapper = static fn (int $userId, array $args): array => ['orchestra_members_profile_edit_own'];
        $registry = (new CapabilityRegistry())->registerMetaCapability('orchestra_members_profile_edit', $mapper);

        $this->assertSame($mapper, $registry->metaCapabilityMapper('orchestra_members_profile_edit'));
        $this->assertNull($registry->metaCapabilityMapper('edit_post'));
    }

    public function testFingerprintIgnoresRegistrationOrder(): void
    {
        $first = (new CapabilityRegistry())
            ->register('orchestra_library_piece_read', [Roles::MEMBER, Roles::CONDUCTOR])
            ->register('orchestra_library_piece_create', [Roles::CONDUCTOR]);
        $second = (new CapabilityRegistry())
            ->register('orchestra_library_piece_create', [Roles::CONDUCTOR])
            ->register('orchestra_library_piece_read', [Roles::CONDUCTOR, Roles::MEMBER]);

        $this->assertSame($first->fingerprint(), $second->fingerprint());
    }

    public function testFingerprintChangesWithGrants(): void
    {
        $before = (new CapabilityRegistry())->register('orchestra_library_piece_read', [Roles::MEMBER]);
        $after = (new CapabilityRegistry())
            ->register('orchestra_library_piece_read', [Roles::MEMBER, Roles::ORGANIZER]);

        $this->assertNotSame($before->fingerprint(), $after->fingerprint());
    }
}
