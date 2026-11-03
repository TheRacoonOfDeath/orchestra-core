<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

use Orchestra\Core\Ui\LoginPageRedirect;
use PHPUnit\Framework\TestCase;

final class LoginPageRedirectTest extends TestCase
{
    private const PAGE = 'https://example.com/login/';
    private const ADMIN = 'https://example.com/wp-admin/';

    public function testDownloadsReturnFromTheOrchestraLoginPage(): void
    {
        $download = self::ADMIN . 'admin-post.php?action=orchestra_library_download&file=3';

        $this->assertSame(
            self::PAGE . '?redirect_to=' . \rawurlencode($download),
            LoginPageRedirect::orchestraLoginUrl(self::PAGE, $download, false)
        );
    }

    public function testPagesOfTheSiteKeepTheirQueryInTheRedirect(): void
    {
        $this->assertSame(
            'https://example.com/?page_id=5&redirect_to=' . \rawurlencode('https://example.com/projects/'),
            LoginPageRedirect::orchestraLoginUrl(
                'https://example.com/?page_id=5',
                'https://example.com/projects/',
                false
            )
        );
    }

    public function testForcedLoginsAndLoginsWithoutDestinationKeepWordPressLogin(): void
    {
        $this->assertNull(LoginPageRedirect::orchestraLoginUrl(self::PAGE, 'https://example.com/projects/', true));
        $this->assertNull(LoginPageRedirect::orchestraLoginUrl(self::PAGE, '', false));
    }

    public function testOnlyOrchestraActionsOfAdminPostAreOrchestraLinks(): void
    {
        $this->assertTrue(LoginPageRedirect::isOrchestraAction(
            self::ADMIN . 'admin-post.php?file=3&action=orchestra_library_download',
            self::ADMIN
        ));
        $this->assertFalse(LoginPageRedirect::isOrchestraAction(
            self::ADMIN . 'admin-post.php?action=shop_export',
            self::ADMIN
        ));
        $this->assertFalse(LoginPageRedirect::isOrchestraAction(self::ADMIN . 'admin-post.php', self::ADMIN));
        $this->assertFalse(LoginPageRedirect::isOrchestraAction(
            self::ADMIN . 'edit.php?action=orchestra_library_download',
            self::ADMIN
        ));
        $this->assertFalse(LoginPageRedirect::isOrchestraAction(
            'https://example.com/shop/?action=orchestra_library_download',
            self::ADMIN
        ));
    }
}
