<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

use Orchestra\Core\Tests\Fakes\WordPress;
use Orchestra\Core\Ui\Menu;
use Orchestra\Core\Ui\MenuItem;
use PHPUnit\Framework\TestCase;

final class MenuTest extends TestCase
{
    protected function setUp(): void
    {
        WordPress::reset();
        Menu::reset();
    }

    public function testPluginsAddItemsOrderedByPosition(): void
    {
        WordPress::$filters[Menu::REGISTER_ACTION] = static function (Menu $menu): void {
            $menu->add(self::item('orchestra_email', 'Email', 80))
                ->add(self::item('orchestra_projects', 'Projects', 20))
                ->add(self::item('orchestra_library', 'Library', 30));
        };

        $labels = \array_map(static fn (MenuItem $item): string => $item->label, Menu::items());

        $this->assertSame(['Projects', 'Library', 'Email'], $labels);
    }

    public function testItemsAreShownOnlyToLoggedInUsersWhoMayUseThem(): void
    {
        $item = new MenuItem('orchestra_email', 'Email', '', static fn (int $userId): bool => $userId === 5);

        $this->assertTrue($item->visibleTo(5));
        $this->assertFalse($item->visibleTo(6));
        $this->assertFalse($item->visibleTo(0));
    }

    private static function item(string $shortcode, string $label, int $position): MenuItem
    {
        return new MenuItem($shortcode, $label, '', static fn (int $userId): bool => true, $position);
    }
}
