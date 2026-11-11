<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

use Orchestra\Core\Ui\LoginShortcode;
use PHPUnit\Framework\TestCase;

final class LoginShortcodeTest extends TestCase
{
    public function testAnUnknownAccountAndAWrongPasswordGetTheSameMessage(): void
    {
        $unknownName = LoginShortcode::failureMessage(['invalid_username'], 'The username jane is not registered.');
        $unknownEmail = LoginShortcode::failureMessage(['invalid_email'], 'Unknown email address.');
        $wrongPassword = LoginShortcode::failureMessage(['incorrect_password'], 'The password for jane is wrong.');

        $this->assertSame($unknownName, $unknownEmail);
        $this->assertSame($unknownName, $wrongPassword);
        $this->assertStringNotContainsString('jane', $wrongPassword);
    }

    public function testOtherReasonsKeepTheirMessage(): void
    {
        $this->assertSame(
            'Your account is inactive.',
            LoginShortcode::failureMessage(['orchestra_members_inactive'], 'Your account is inactive.')
        );
        $this->assertSame('', LoginShortcode::failureMessage([], ''));
    }
}
