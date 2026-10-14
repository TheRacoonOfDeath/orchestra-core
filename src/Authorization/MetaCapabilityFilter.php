<?php

declare(strict_types=1);

namespace Orchestra\Core\Authorization;

/**
 * Resolves registered meta capabilities through the WordPress `map_meta_cap` filter.
 */
final class MetaCapabilityFilter
{
    public function __construct(private readonly CapabilityRegistry $registry)
    {
    }

    /**
     * @param array<string> $caps The primitive capabilities WordPress resolved so far
     * @param array<mixed> $args The extra arguments passed to `current_user_can()`
     * @return array<string>
     */
    public function __invoke(array $caps, string $capability, int $userId, array $args): array
    {
        $mapper = $this->registry->metaCapabilityMapper($capability);

        return $mapper === null ? $caps : $mapper($userId, $args);
    }
}
