<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Files;

use Orchestra\Core\Files\PrivateDirectoryCheck;
use PHPUnit\Framework\TestCase;

final class PrivateDirectoryCheckTest extends TestCase
{
    private const ROOTS = [
        '/var/www/html/wp-content/uploads' => 'https://example.com/wp-content/uploads',
        '/var/www/html/wp-content' => 'https://example.com/wp-content',
        '/var/www/html/' => 'https://example.com/',
    ];

    public function testTheDefaultFolderInTheUploadsHasAUrl(): void
    {
        $this->assertSame(
            'https://example.com/wp-content/uploads/orchestra-library-abc123',
            PrivateDirectoryCheck::urlFor('/var/www/html/wp-content/uploads/orchestra-library-abc123/', self::ROOTS)
        );
    }

    public function testFoldersElsewhereUnderTheWebRootHaveAUrl(): void
    {
        $this->assertSame(
            'https://example.com/private/sheet%20music',
            PrivateDirectoryCheck::urlFor('/var/www/html/private/sheet music', self::ROOTS)
        );
    }

    public function testFoldersOutsideTheWebRootHaveNoUrl(): void
    {
        $this->assertNull(PrivateDirectoryCheck::urlFor('/var/www/orchestra-private/library', self::ROOTS));
        $this->assertNull(PrivateDirectoryCheck::urlFor('/var/www/html-private/library', self::ROOTS));
    }

    public function testAFolderIsPublicOnlyIfTheTestFileCameBack(): void
    {
        $this->assertSame(PrivateDirectoryCheck::PUBLIC, PrivateDirectoryCheck::statusOfProbe(200, 'abc', 'abc'));
        $this->assertSame(PrivateDirectoryCheck::PRIVATE, PrivateDirectoryCheck::statusOfProbe(403, 'abc', 'abc'));
        $this->assertSame(
            PrivateDirectoryCheck::PRIVATE,
            PrivateDirectoryCheck::statusOfProbe(200, '<html>Page not found</html>', 'abc')
        );
    }
}
