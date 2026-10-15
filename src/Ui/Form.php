<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * Forms that post to `admin-post.php`, carry a nonce and return to the page they were sent from.
 */
final class Form
{
    /**
     * Opens a form. Close it with `</form>`.
     *
     * @param array<string, string|int> $fields Hidden fields, e.g. `redirect_to`
     * @param array<string, string> $attributes Further attributes, e.g. `data-orchestra-confirm`
     */
    public static function open(
        string $action,
        string $nonceAction,
        array $fields = [],
        array $attributes = [],
        bool $multipart = false,
    ): string {
        $attributes = ['method' => 'post', 'action' => \admin_url('admin-post.php')] + $attributes;
        if ($multipart) {
            $attributes['enctype'] = 'multipart/form-data';
        }

        $html = '<form';
        foreach ($attributes as $name => $value) {
            $html .= ' ' . \esc_attr($name) . '="' . ($name === 'action' ? \esc_url($value) : \esc_attr($value)) . '"';
        }
        $html .= '>';

        // Forms return to the current page unless the caller passes another `redirect_to`.
        $fields = ['action' => $action] + $fields + [Redirect::FIELD => Redirect::currentUrl()];
        foreach ($fields as $name => $value) {
            $html .= self::hidden($name, (string) $value);
        }

        return $html . \wp_nonce_field($nonceAction, '_wpnonce', false, false);
    }

    /**
     * A form with a single button.
     *
     * @param array<string, string|int> $fields
     * @param string|null $confirm Message the user must confirm first
     */
    public static function button(
        string $action,
        string $nonceAction,
        string $label,
        array $fields = [],
        ?string $confirm = null,
        string $style = '',
    ): string {
        $attributes = ['class' => 'orchestra-inline-form'];
        if ($confirm !== null) {
            $attributes['data-orchestra-confirm'] = $confirm;
        }

        return self::open($action, $nonceAction, $fields, $attributes)
            . self::submit($label, $style) . '</form>';
    }

    /**
     * @param string $style Space-separated button variants: primary, danger, small, link
     */
    public static function submit(string $label, string $style = '', string $name = '', string $value = ''): string
    {
        return '<button type="submit" class="' . \esc_attr(self::buttonClass($style)) . '"'
            . ($name === '' ? '' : ' name="' . \esc_attr($name) . '" value="' . \esc_attr($value) . '"')
            . '>' . \esc_html($label) . '</button>';
    }

    /**
     * @param string $style Space-separated button variants: primary, danger, small, link
     */
    public static function link(string $url, string $label, string $style = ''): string
    {
        return '<a class="' . \esc_attr(self::buttonClass($style)) . '" href="' . \esc_url($url) . '">'
            . \esc_html($label) . '</a>';
    }

    public static function hidden(string $name, string $value): string
    {
        return '<input type="hidden" name="' . \esc_attr($name) . '" value="' . \esc_attr($value) . '">';
    }

    private static function buttonClass(string $style): string
    {
        $classes = ['orchestra-button'];
        foreach (\preg_split('/\s+/', \trim($style)) ?: [] as $variant) {
            if ($variant !== '') {
                $classes[] = 'orchestra-button--' . $variant;
            }
        }

        return \implode(' ', $classes);
    }
}
