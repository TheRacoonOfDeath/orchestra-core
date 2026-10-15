<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * Building blocks for Orchestra pages.
 */
final class Html
{
    /**
     * Wraps the output of a shortcode: loads the UI assets and shows pending notices.
     */
    public static function page(string $content, bool $withNotices = true): string
    {
        Assets::enqueue();

        return '<div class="orchestra">' . ($withNotices ? Notices::render() : '') . $content . '</div>';
    }

    /**
     * The same for a screen in wp-admin.
     */
    public static function adminPage(string $title, string $content, string $actions = ''): string
    {
        Assets::enqueue();

        return '<div class="wrap"><h1 class="wp-heading-inline">' . \esc_html($title) . '</h1>' . $actions
            . '<hr class="wp-header-end"><div class="orchestra orchestra--admin">' . $content . '</div></div>';
    }

    /**
     * Shown by shortcodes to logged-out visitors.
     */
    public static function loginRequired(string $message): string
    {
        $url = Pages::url(Pages::LOGIN) ?? \wp_login_url(Redirect::currentUrl());
        if (Pages::url(Pages::LOGIN) !== null) {
            $url = \add_query_arg('redirect_to', \rawurlencode(Redirect::currentUrl()), $url);
        }

        return self::page(
            '<div class="orchestra-empty"><p>' . \esc_html($message) . '</p><p>'
            . Form::link($url, \__('Log in', 'orchestra-core'), 'primary') . '</p></div>',
            false
        );
    }

    public static function notAllowed(string $message): string
    {
        return self::page('<div class="orchestra-empty"><p>' . \esc_html($message) . '</p></div>', false);
    }

    public static function empty(string $message): string
    {
        return '<div class="orchestra-empty"><p>' . \esc_html($message) . '</p></div>';
    }

    /**
     * @param string $variant success, warning, danger, info or empty
     */
    public static function badge(string $label, string $variant = ''): string
    {
        $class = 'orchestra-badge' . ($variant === '' ? '' : ' orchestra-badge--' . $variant);

        return '<span class="' . \esc_attr($class) . '">' . \esc_html($label) . '</span>';
    }

    /**
     * A table that turns into stacked cards on phones.
     *
     * @param list<string> $headings Plain text
     * @param list<list<string>> $rows Cells as HTML, already escaped
     * @param bool $stacked Rows as stacked blocks at every width, e.g. for rows with forms
     */
    public static function table(array $headings, array $rows, string $emptyMessage = '', bool $stacked = false): string
    {
        if ($rows === [] && $emptyMessage !== '') {
            return self::empty($emptyMessage);
        }

        $class = $stacked ? 'orchestra-table orchestra-table--stacked' : 'orchestra-table';
        $html = '<div class="orchestra-table-wrap"><table class="' . $class . '"><thead><tr>';
        foreach ($headings as $heading) {
            $html .= '<th scope="col">' . \esc_html($heading) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $index => $cell) {
                $html .= '<td data-label="' . \esc_attr($headings[$index] ?? '') . '">' . $cell . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table></div>';
    }

    /**
     * @param list<array{string, string}> $items Label (plain text) and value (HTML, already escaped)
     */
    public static function details(array $items): string
    {
        $html = '<dl class="orchestra-details">';
        foreach ($items as [$label, $value]) {
            $html .= '<dt>' . \esc_html($label) . '</dt><dd>' . $value . '</dd>';
        }

        return $html . '</dl>';
    }

    /**
     * @param array<string, string> $tabs key => label
     * @param callable(string): string $url Returns the URL of a tab
     */
    public static function tabs(array $tabs, string $current, callable $url): string
    {
        $html = '<nav class="orchestra-tabs" aria-label="' . \esc_attr__('Sections', 'orchestra-core') . '">';
        foreach ($tabs as $key => $label) {
            $html .= '<a href="' . \esc_url($url($key)) . '"' . ($key === $current ? ' aria-current="page"' : '') . '>'
                . \esc_html($label) . '</a>';
        }

        return $html . '</nav>';
    }

    /**
     * A form field with label and optional hint.
     */
    public static function field(string $id, string $label, string $control, string $hint = ''): string
    {
        return '<div class="orchestra-field"><label for="' . \esc_attr($id) . '">' . \esc_html($label) . '</label>'
            . $control . ($hint === '' ? '' : '<span class="orchestra-field__hint">' . \esc_html($hint) . '</span>')
            . '</div>';
    }
}
