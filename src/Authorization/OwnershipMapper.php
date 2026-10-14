<?php

declare(strict_types=1);

namespace Orchestra\Core\Authorization;

use Closure;

/**
 * Maps a meta capability onto an "own" or an "others" primitive capability, depending on whether
 * the checked object belongs to the user.
 *
 * The check is denied when no object ID is passed or the owner cannot be determined, so a
 * forgotten argument never grants access.
 *
 * Usage:
 *
 *     $registry->registerMetaCapability(
 *         'orchestra_members_profile_edit',
 *         OwnershipMapper::forUsers('orchestra_members_profile_edit_own', 'orchestra_members_profile_edit_others')
 *     );
 *     current_user_can('orchestra_members_profile_edit', $userId);
 *
 * Resources stored as custom post types do not need this class: register the post type with
 * `capability_type` and `map_meta_cap => true` and WordPress handles ownership itself.
 */
final class OwnershipMapper
{
    public const DENY = 'do_not_allow';

    /**
     * @param Closure(int $objectId): ?int $ownerOf Returns the owning user's ID, or null if unknown
     */
    public function __construct(
        private readonly string $ownCapability,
        private readonly string $othersCapability,
        private readonly Closure $ownerOf,
    ) {
    }

    /**
     * For objects that are users themselves, e.g. member profiles.
     */
    public static function forUsers(string $ownCapability, string $othersCapability): self
    {
        return new self($ownCapability, $othersCapability, static fn (int $userId): int => $userId);
    }

    /**
     * @param array<mixed> $args The extra arguments passed to `current_user_can()`
     * @return list<string>
     */
    public function __invoke(int $userId, array $args): array
    {
        $objectId = self::objectId($args[0] ?? null);
        if ($objectId === null) {
            return [self::DENY];
        }

        $ownerId = ($this->ownerOf)($objectId);
        if ($ownerId === null) {
            return [self::DENY];
        }

        return [$userId > 0 && $ownerId === $userId ? $this->ownCapability : $this->othersCapability];
    }

    private static function objectId(mixed $value): ?int
    {
        if (\is_string($value) && \ctype_digit($value)) {
            $value = (int) $value;
        }

        return \is_int($value) && $value > 0 ? $value : null;
    }
}
