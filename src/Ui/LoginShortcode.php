<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * `[orchestra_login]`: the login form on a normal page. Failed logins come back to the page with
 * WordPress's message, e.g. that the account is inactive.
 */
final class LoginShortcode
{
    private const FIELD = 'orchestra_login_page';
    private const ERROR_PARAMETER = 'orchestra_login_error';
    private const ERROR_TRANSIENT_PREFIX = 'orchestra_core_login_error_';

    public static function register(): void
    {
        \add_shortcode(Pages::LOGIN, [self::class, 'render']);
        \add_action('wp_login_failed', [self::class, 'returnToPage'], 10, 2);
    }

    /**
     * @internal Shortcode callback.
     */
    public static function render(): string
    {
        return Html::page(self::content(), false);
    }

    /**
     * The form, or who is logged in.
     */
    public static function content(): string
    {
        if (\is_user_logged_in()) {
            $user = \wp_get_current_user();

            return '<div class="orchestra-card orchestra-login"><p>' . \esc_html(\sprintf(
                /* translators: %s: user's name */
                \__('You are logged in as %s.', 'orchestra-core'),
                $user->display_name
            )) . '</p><div class="orchestra-actions">'
                . Form::link(AdminAccess::homeUrl(), \__('Go to the orchestra pages', 'orchestra-core'), 'primary')
                . Form::link(\wp_logout_url(Redirect::currentUrl()), \__('Log out', 'orchestra-core'))
                . '</div></div>';
        }

        $html = '<div class="orchestra-card orchestra-login">';
        $error = self::takeError();
        if ($error !== '') {
            $html .= '<div class="orchestra-notice orchestra-notice--error" role="alert">' . \wp_kses_post($error)
                . '</div>';
        }

        $requested = \is_string($_GET['redirect_to'] ?? null) ? \wp_unslash($_GET['redirect_to']) : '';
        $redirect = \wp_validate_redirect($requested, AdminAccess::homeUrl());
        $hidden = Form::hidden(self::FIELD, Redirect::currentUrl());
        $form = \wp_login_form([
            'echo' => false,
            'redirect' => $redirect,
            'label_username' => \__('Username or email address', 'orchestra-core'),
        ]);
        $form = \str_replace('</form>', $hidden . '</form>', (string) $form);
        $form = (string) \preg_replace('/(<input[^>]+id="(?:user_login|user_pass)")/', '$1 required', $form);

        $lostPassword = \wp_lostpassword_url(Redirect::currentUrl());

        return $html . $form . '<p class="orchestra-muted"><a href="' . \esc_url($lostPassword) . '">'
            . \esc_html__('Forgot your password?', 'orchestra-core') . '</a></p></div>';
    }

    /**
     * @internal Hooked to `wp_login_failed`.
     */
    public static function returnToPage(string $username, mixed $error = null): void
    {
        $page = \is_string($_POST[self::FIELD] ?? null) ? \wp_unslash($_POST[self::FIELD]) : '';
        if ($page === '') {
            return;
        }
        $page = \wp_validate_redirect($page, '');
        if ($page === '') {
            return;
        }

        $message = $error instanceof \WP_Error ? \implode(' ', $error->get_error_messages()) : '';
        $token = \wp_generate_password(20, false);
        $message = $message !== '' ? $message : \__('The login failed.', 'orchestra-core');
        \set_transient(self::ERROR_TRANSIENT_PREFIX . $token, $message, 120);

        $page = \remove_query_arg(self::ERROR_PARAMETER, $page);
        \wp_safe_redirect(\add_query_arg(self::ERROR_PARAMETER, $token, $page));
        exit;
    }

    private static function takeError(): string
    {
        $token = \is_string($_GET[self::ERROR_PARAMETER] ?? null) ? \sanitize_key($_GET[self::ERROR_PARAMETER]) : '';
        if ($token === '') {
            return '';
        }
        $message = \get_transient(self::ERROR_TRANSIENT_PREFIX . $token);
        \delete_transient(self::ERROR_TRANSIENT_PREFIX . $token);

        return \is_string($message) ? $message : '';
    }
}
