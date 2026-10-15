<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * Messages that survive the redirect after a form was sent, shown on the next Orchestra page or
 * wp-admin screen of the same user.
 */
final class Notices
{
    private const TRANSIENT_PREFIX = 'orchestra_core_notices_';

    public static function register(): void
    {
        \add_action('admin_notices', [self::class, 'renderInAdmin']);
    }

    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('error', $message);
    }

    /**
     * @param list<string> $messages
     */
    public static function errors(array $messages): void
    {
        foreach ($messages as $message) {
            self::error($message);
        }
    }

    /**
     * Returns and forgets the stored notices.
     *
     * @return list<array{string, string}> type ("success" or "error") and message
     */
    public static function take(): array
    {
        $notices = self::stored();
        if ($notices !== []) {
            \delete_transient(self::transientName());
        }

        return $notices;
    }

    /**
     * The stored notices as HTML for an Orchestra page.
     */
    public static function render(): string
    {
        $html = '';
        foreach (self::take() as [$type, $message]) {
            $html .= self::html($type, $message);
        }

        return $html;
    }

    public static function html(string $type, string $message): string
    {
        $role = $type === 'error' ? 'alert' : 'status';

        return '<div class="orchestra-notice orchestra-notice--' . \esc_attr($type) . '" role="' . $role . '"><p>'
            . \esc_html($message) . '</p></div>';
    }

    /**
     * @internal Hooked to `admin_notices`.
     */
    public static function renderInAdmin(): void
    {
        foreach (self::take() as [$type, $message]) {
            \printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                \esc_attr($type),
                \esc_html($message)
            );
        }
    }

    private static function add(string $type, string $message): void
    {
        if (\get_current_user_id() === 0) {
            return;
        }
        $notices = self::stored();
        $notices[] = [$type, $message];
        \set_transient(self::transientName(), $notices, 5 * MINUTE_IN_SECONDS);
    }

    /**
     * @return list<array{string, string}>
     */
    private static function stored(): array
    {
        $stored = \get_transient(self::transientName());
        if (!\is_array($stored)) {
            return [];
        }

        $notices = [];
        foreach ($stored as $notice) {
            if (\is_array($notice) && \is_string($notice[0] ?? null) && \is_string($notice[1] ?? null)) {
                $notices[] = [$notice[0], $notice[1]];
            }
        }

        return $notices;
    }

    private static function transientName(): string
    {
        return self::TRANSIENT_PREFIX . \get_current_user_id();
    }
}
