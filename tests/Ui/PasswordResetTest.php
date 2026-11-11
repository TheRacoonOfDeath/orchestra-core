<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

use Orchestra\Core\Tests\Fakes\WordPress;
use Orchestra\Core\Ui\PasswordReset;
use PHPUnit\Framework\TestCase;

final class PasswordResetTest extends TestCase
{
    public function testANewPasswordNeedsEightCharactersAndTheSameRepetition(): void
    {
        $this->assertSame('short', PasswordReset::passwordError('seven77', 'seven77'));
        $this->assertSame('mismatch', PasswordReset::passwordError('long enough', 'long enougH'));
        $this->assertNull(PasswordReset::passwordError('long enough', 'long enough'));
        $this->assertSame('short', PasswordReset::passwordError('äöüäöüä', 'äöüäöüä'), 'characters, not bytes');
    }

    public function testOnlyKnownStatesHaveAMessage(): void
    {
        $this->assertSame('success', PasswordReset::messageFor('sent')[0] ?? null);
        $this->assertSame('error', PasswordReset::messageFor('invalid')[0] ?? null);
        $this->assertNull(PasswordReset::messageFor('<script>'));
        $this->assertNull(PasswordReset::messageFor(''));
    }

    public function testAnAccountGetsOneEmailUntilTheIntervalHasPassed(): void
    {
        WordPress::reset();
        $jane = WordPress::addUser(7, 'jane@example.org');
        $john = WordPress::addUser(8, 'john@example.org');

        $this->assertFalse(PasswordReset::limitEmails(new \WP_Error(), $jane)->has_errors(), 'first email');
        $this->assertTrue(PasswordReset::limitEmails(new \WP_Error(), $jane)->has_errors(), 'second email');
        $this->assertFalse(PasswordReset::limitEmails(new \WP_Error(), $john)->has_errors(), 'other account');
    }

    public function testUnknownAccountsAreLeftToWordPress(): void
    {
        WordPress::reset();
        $errors = new \WP_Error();
        $errors->add('invalid_email', 'There is no account with that email address.');

        $this->assertSame(['invalid_email'], PasswordReset::limitEmails($errors, false)->get_error_codes());
        $this->assertSame([], WordPress::$options);
    }
}
