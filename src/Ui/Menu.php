<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * The areas of the Orchestra pages. Plugins add theirs during {@see REGISTER_ACTION}.
 *
 *     add_action(Menu::REGISTER_ACTION, function (Menu $menu): void {
 *         $menu->add(new MenuItem('orchestra_projects', 'Projects', 'Your projects', $visible, 20));
 *     });
 */
final class Menu
{
    public const REGISTER_ACTION = 'orchestra_core_register_menu_items';

    /** @var array<string, MenuItem>|null */
    private static ?array $items = null;

    public function add(MenuItem $item): self
    {
        self::$items[$item->shortcode] = $item;

        return $this;
    }

    /**
     * @return list<MenuItem> ordered by position
     */
    public static function items(): array
    {
        if (self::$items === null) {
            self::$items = [];
            \do_action(self::REGISTER_ACTION, new self());
        }
        $items = \array_values(self::$items);
        \usort(
            $items,
            static fn (MenuItem $a, MenuItem $b): int => [$a->position, $a->label] <=> [$b->position, $b->label]
        );

        return $items;
    }

    /**
     * The items the user can use and whose page exists, with their URLs.
     *
     * @return list<array{MenuItem, string}>
     */
    public static function linksFor(int $userId): array
    {
        $links = [];
        foreach (self::items() as $item) {
            $url = Pages::url($item->shortcode);
            if ($url !== null && $item->visibleTo($userId)) {
                $links[] = [$item, $url];
            }
        }

        return $links;
    }

    /**
     * Only for tests.
     */
    public static function reset(): void
    {
        self::$items = null;
    }
}
