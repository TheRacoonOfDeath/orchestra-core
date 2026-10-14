<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Authorization;

use Orchestra\Core\Authorization\CapabilityRegistry;
use Orchestra\Core\Authorization\MetaCapabilityFilter;
use PHPUnit\Framework\TestCase;

final class MetaCapabilityFilterTest extends TestCase
{
    public function testLeavesUnregisteredCapabilitiesUntouched(): void
    {
        $filter = new MetaCapabilityFilter(new CapabilityRegistry());

        $this->assertSame(['edit_others_posts'], $filter(['edit_others_posts'], 'edit_post', 7, [10]));
    }

    public function testDelegatesRegisteredMetaCapabilitiesToTheirMapper(): void
    {
        $registry = (new CapabilityRegistry())->registerMetaCapability(
            'orchestra_members_profile_edit',
            static fn (int $userId, array $args): array => ["user {$userId}", ...$args]
        );
        $filter = new MetaCapabilityFilter($registry);

        $this->assertSame(
            ['user 7', 10],
            $filter(['orchestra_members_profile_edit'], 'orchestra_members_profile_edit', 7, [10])
        );
    }
}
