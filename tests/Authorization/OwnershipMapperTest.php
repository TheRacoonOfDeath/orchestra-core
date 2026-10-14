<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Authorization;

use Orchestra\Core\Authorization\OwnershipMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OwnershipMapperTest extends TestCase
{
    private const OWN = 'orchestra_projects_participation_respond_own';
    private const OTHERS = 'orchestra_projects_participation_respond_others';

    /** Participation 10 belongs to user 7; the owner of every other ID is unknown. */
    private OwnershipMapper $mapper;

    protected function setUp(): void
    {
        $ownerOf = static fn (int $id): ?int => $id === 10 ? 7 : null;
        $this->mapper = new OwnershipMapper(self::OWN, self::OTHERS, $ownerOf);
    }

    public function testOwnerNeedsOwnCapability(): void
    {
        $this->assertSame([self::OWN], ($this->mapper)(7, [10]));
    }

    public function testOtherUserNeedsOthersCapability(): void
    {
        $this->assertSame([self::OTHERS], ($this->mapper)(8, [10]));
    }

    public function testLoggedOutUserNeedsOthersCapability(): void
    {
        $this->assertSame([self::OTHERS], ($this->mapper)(0, [10]));
    }

    public function testAcceptsNumericStringIds(): void
    {
        $this->assertSame([self::OWN], ($this->mapper)(7, ['10']));
    }

    /**
     * @param array<mixed> $args
     */
    #[DataProvider('invalidObjectArguments')]
    public function testDeniesWithoutValidObjectId(array $args): void
    {
        $this->assertSame([OwnershipMapper::DENY], ($this->mapper)(7, $args));
    }

    /**
     * @return iterable<string, array{array<mixed>}>
     */
    public static function invalidObjectArguments(): iterable
    {
        yield 'no argument' => [[]];
        yield 'null' => [[null]];
        yield 'zero' => [[0]];
        yield 'negative' => [[-10]];
        yield 'non-numeric string' => [['abc']];
        yield 'float' => [[10.0]];
    }

    public function testDeniesWhenOwnerIsUnknown(): void
    {
        $this->assertSame([OwnershipMapper::DENY], ($this->mapper)(7, [11]));
    }

    public function testUsersOwnThemselves(): void
    {
        $mapper = OwnershipMapper::forUsers(self::OWN, self::OTHERS);

        $this->assertSame([self::OWN], $mapper(7, [7]));
        $this->assertSame([self::OTHERS], $mapper(7, [8]));
    }
}
