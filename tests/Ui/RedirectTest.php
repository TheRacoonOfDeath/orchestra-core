<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

use Orchestra\Core\Tests\Fakes\WordPress;
use Orchestra\Core\Ui\Redirect;
use PHPUnit\Framework\TestCase;

final class RedirectTest extends TestCase
{
    protected function setUp(): void
    {
        WordPress::reset();
    }

    public function testReturnsToThePageTheFormWasSentFrom(): void
    {
        $_REQUEST[Redirect::FIELD] = 'https://orchestra.example/projects/?orchestra_project=5';

        $this->assertSame('https://orchestra.example/projects/?orchestra_project=5', Redirect::target());
    }

    public function testNeverRedirectsToOtherSites(): void
    {
        $_REQUEST[Redirect::FIELD] = 'https://evil.example/phishing';
        WordPress::$referer = 'https://orchestra.example/members/';

        $this->assertSame('https://orchestra.example/members/', Redirect::target());
    }

    public function testFallsBackToTheRefererAndTheHomePage(): void
    {
        $this->assertSame('https://orchestra.example/', Redirect::target());

        WordPress::$referer = 'https://orchestra.example/library/';
        $this->assertSame('https://orchestra.example/library/', Redirect::target());
        $this->assertSame('https://orchestra.example/x', Redirect::target('https://orchestra.example/x'));
    }
}
