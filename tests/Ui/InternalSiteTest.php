<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

use Orchestra\Core\Ui\InternalSite;
use PHPUnit\Framework\TestCase;

final class InternalSiteTest extends TestCase
{
    public function testVisitorsCannotListUsers(): void
    {
        $endpoints = [
            '/wp/v2/pages' => ['pages'],
            '/wp/v2/users' => ['users'],
            '/wp/v2/users/(?P<id>[\d]+)' => ['user'],
            '/wp/v2/users/me' => ['me'],
        ];

        $this->assertSame(
            ['/wp/v2/pages' => ['pages'], '/wp/v2/users/me' => ['me']],
            InternalSite::withoutUserRoutes($endpoints)
        );
    }

    public function testOembedDataDoesNotNameTheAuthor(): void
    {
        $data = ['title' => 'Projects', 'author_name' => 'admin', 'author_url' => 'https://example.com/author/admin/'];

        $this->assertSame(['title' => 'Projects'], InternalSite::withoutAuthor($data));
    }
}
