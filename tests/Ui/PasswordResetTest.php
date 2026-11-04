<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

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
}
