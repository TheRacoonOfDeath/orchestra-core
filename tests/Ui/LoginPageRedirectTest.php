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
            LoginPageRedirect::orchestraLoginUrl(self::PAGE, $download, false, self::ADMIN)
        );
    }

    public function testPagesOfTheSiteKeepTheirQueryInTheRedirect(): void
    {
        $this->assertSame(
            'https://example.com/?page_id=5&redirect_to=' . \rawurlencode('https://example.com/projects/'),
            LoginPageRedirect::orchestraLoginUrl(
                'https://example.com/?page_id=5',
                'https://example.com/projects/',
                false,
                self::ADMIN
            )
        );
        $this->assertSame(self::PAGE, LoginPageRedirect::orchestraLoginUrl(self::PAGE, '', false, self::ADMIN));
    }

    public function testWpAdminAndForcedLoginsKeepWordPressLogin(): void
    {
        $this->assertNull(
            LoginPageRedirect::orchestraLoginUrl(self::PAGE, self::ADMIN . 'edit.php', false, self::ADMIN)
        );
        $this->assertNull(LoginPageRedirect::orchestraLoginUrl(self::PAGE, 'https://example.com/', true, self::ADMIN));
    }
}
