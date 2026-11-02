<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

use Orchestra\Core\Tests\Fakes\WordPress;
use Orchestra\Core\Ui\Menu;
use Orchestra\Core\Ui\MenuItem;
use Orchestra\Core\Ui\PageSetup;
use Orchestra\Core\Ui\Pages;
use PHPUnit\Framework\TestCase;

final class PageSetupTest extends TestCase
{
    protected function setUp(): void
    {
        WordPress::reset();
        Menu::reset();
        Pages::flush();
        WordPress::$filters[Menu::REGISTER_ACTION] = static function (Menu $menu): void {
            $menu->add(self::item('orchestra_seating', 'Seating', 50))
                ->add(self::item('orchestra_projects', 'Projects', 20));
        };
    }

    public function testListsTheMissingPagesOfANewSite(): void
    {
        $this->assertSame([
            'orchestra_portal' => 'Orchestra',
            'orchestra_login' => 'Login',
            'orchestra_projects' => 'Projects',
            'orchestra_seating' => 'Seating',
        ], PageSetup::offered());
    }

    public function testOffersTheAreasThatHaveNoPageYet(): void
    {
        // The site has the start page, the login and the projects; seating is new.
        WordPress::$options['orchestra_core_pages'] = [
            'orchestra_portal' => 3,
            'orchestra_login' => 4,
            'orchestra_projects' => 5,
        ];

        $this->assertSame(['orchestra_seating' => 'Seating'], PageSetup::offered());
    }

    public function testDismissedPagesAreNotOfferedAgain(): void
    {
        WordPress::$options['orchestra_core_page_setup_dismissed'] = ['orchestra_portal', 'orchestra_login'];

        $this->assertSame(
            ['orchestra_projects' => 'Projects', 'orchestra_seating' => 'Seating'],
            PageSetup::offered()
        );
        $this->assertCount(4, PageSetup::missing());
    }

    public function testADismissalOfAnOlderVersionDismissesNothingInParticular(): void
    {
        WordPress::$options['orchestra_core_page_setup_dismissed'] = 1;

        $this->assertCount(4, PageSetup::offered());
    }

    private static function item(string $shortcode, string $label, int $position): MenuItem
    {
        return new MenuItem($shortcode, $label, '', static fn (int $userId): bool => true, $position);
    }
}
