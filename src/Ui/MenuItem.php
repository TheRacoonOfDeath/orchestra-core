<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

use Closure;

/**
 * An area of the Orchestra pages, e.g. "Projects", shown in `[orchestra_menu]` and on the portal.
 */
final class MenuItem
{
    /**
     * @param string $shortcode The shortcode of the area; the item links to the page containing it
     * @param Closure(int $userId): bool $visible Whether the user can use the area
     * @param int $position Smaller numbers come first
     */
    public function __construct(
        public readonly string $shortcode,
        public readonly string $label,
        public readonly string $description,
        private readonly Closure $visible,
        public readonly int $position = 50,
    ) {
    }

    public function visibleTo(int $userId): bool
    {
        return $userId > 0 && ($this->visible)($userId) === true;
    }
}
