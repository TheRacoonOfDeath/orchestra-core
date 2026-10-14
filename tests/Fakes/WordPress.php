<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Fakes;

/**
 * In-memory state behind the fake WordPress functions in functions.php.
 */
final class WordPress
{
    /** @var array<string, \WP_Role> */
    public static array $roles = [];

    /** @var array<string, mixed> */
    public static array $options = [];

    public static function reset(): void
    {
        self::$roles = [];
        self::$options = [];
    }
}
