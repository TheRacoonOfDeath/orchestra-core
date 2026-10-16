<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

use Orchestra\Core\Ui\LoginMenuItem;
use PHPUnit\Framework\TestCase;
use stdClass;

final class LoginMenuItemTest extends TestCase
{
    public function testVisitorsSeeLogInAndMembersLogOut(): void
    {
        [$loggedOut] = LoginMenuItem::apply([self::item(LoginMenuItem::URL)], false, 'https://example.com/login/');
        [$loggedIn] = LoginMenuItem::apply([self::item(LoginMenuItem::URL)], true, 'https://example.com/logout');

        $this->assertInstanceOf(stdClass::class, $loggedOut);
        $this->assertInstanceOf(stdClass::class, $loggedIn);
        $this->assertSame(['Log in', 'https://example.com/login/'], [$loggedOut->title, $loggedOut->url]);
        $this->assertSame(['Log out', 'https://example.com/logout'], [$loggedIn->title, $loggedIn->url]);
    }

    public function testLeavesOtherItemsAlone(): void
    {
        [$item] = LoginMenuItem::apply([self::item('https://example.com/about/')], true, 'https://example.com/logout');

        $this->assertInstanceOf(stdClass::class, $item);
        $this->assertSame(['About', 'https://example.com/about/'], [$item->title, $item->url]);
    }

    private static function item(string $url): stdClass
    {
        $item = new stdClass();
        $item->title = 'About';
        $item->url = $url;

        return $item;
    }
}
