<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

use Orchestra\Core\Ui\InternalSite;
use PHPUnit\Framework\TestCase;

final class InternalSiteTest extends TestCase
{
    public function testPagesWithAnOrchestraShortcodeAreInternal(): void
    {
        $this->assertTrue(InternalSite::isOrchestraContent("[orchestra_menu]\n\n[orchestra_projects]"));
        $this->assertTrue(InternalSite::isOrchestraContent('<p>Welcome</p>[orchestra_portal title="Hi"]'));
        $this->assertFalse(InternalSite::isOrchestraContent('<p>Our next concert is on Sunday.</p>'));
    }

    public function testOnlyOrchestraUsersWithoutPublishedPostsAreHidden(): void
    {
        $this->assertTrue(InternalSite::hidesAuthor(['orchestra_member'], 0));
        $this->assertTrue(InternalSite::hidesAuthor(['subscriber', 'orchestra_organizer'], 0));
        $this->assertFalse(InternalSite::hidesAuthor(['orchestra_member'], 3));
        $this->assertFalse(InternalSite::hidesAuthor(['author'], 0));
        $this->assertFalse(InternalSite::hidesAuthor(['administrator'], 0));
    }

    public function testSitemapsLeaveOutOrchestraPagesAndKeepOtherExclusions(): void
    {
        $this->assertSame(
            ['post_type' => 'page', 'post__not_in' => [4, 12, 15]],
            InternalSite::withoutPosts(['post_type' => 'page', 'post__not_in' => [4, 12]], [12, 15])
        );
        $this->assertSame(['post_type' => 'page'], InternalSite::withoutPosts(['post_type' => 'page'], []));
    }
}
