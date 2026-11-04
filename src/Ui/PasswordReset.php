<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

use WP_User;

/**
 * "Forgot your password?" on the Orchestra login page instead of WordPress's login screen: asking
 * for the link and setting the new password both happen on the page with `[orchestra_login]` (or,
 * without one, the start page), in the site's design.
 *
 * - `lostpassword_url` leads to `?orchestra_login=lostpassword`, where the user enters their name
 *   or email address; WordPress sends its usual email (`retrieve_password()`).
 * - The link in that email (and in the welcome email of new accounts) opens
 *   `wp-login.php?action=rp`, which moves the key into a cookie and continues on the Orchestra
 *   page (`?orchestra_login=resetpass`), like WordPress does, so the key does not stay in the URL.
 * - Results are reported with a fixed `orchestra_password` state in the URL, never with user data:
 *   visitors have no notices of their own.
 *
 * Without an Orchestra login or start page, WordPress's screens are used.
 */
final class PasswordReset
{
    public const VIEW = 'orchestra_login';
    public const VIEW_LOST = 'lostpassword';
    public const VIEW_RESET = 'resetpass';
    public const STATE = 'orchestra_password';
    public const MIN_LENGTH = 8;

    private const REQUEST_ACTION = 'orchestra_core_lost_password';
    private const RESET_ACTION = 'orchestra_core_reset_password';
    private const COOKIE = 'orchestra_core_resetpass';
    private const FIELD_LOGIN = 'user_login';
    private const FIELD_PASSWORD = 'new_password';
    private const FIELD_REPEAT = 'new_password_repeat';

    public static function register(): void
    {
        \add_filter('lostpassword_url', [self::class, 'lostPasswordUrl']);
        \add_action('login_form_rp', [self::class, 'continueOnPage']);
        \add_action('login_form_resetpass', [self::class, 'continueOnPage']);
        foreach (['admin_post_nopriv_', 'admin_post_'] as $prefix) {
            \add_action($prefix . self::REQUEST_ACTION, [self::class, 'handleRequest']);
            \add_action($prefix . self::RESET_ACTION, [self::class, 'handleReset']);
        }
    }

    /**
     * The URL of a view of the Orchestra login page, or null if there is no such page.
     *
     * @param array<string, string> $args
     */
    public static function url(string $view, array $args = []): ?string
    {
        $page = Pages::url(Pages::LOGIN) ?? Pages::url(Pages::PORTAL);
        if ($page === null) {
            return null;
        }
        $args = ($view === '' ? [] : [self::VIEW => $view]) + $args;

        return $args === [] ? $page : \add_query_arg(\array_map('rawurlencode', $args), $page);
    }

    /**
     * @internal Hooked to `lostpassword_url`.
     */
    public static function lostPasswordUrl(string $url): string
    {
        return self::url(self::VIEW_LOST) ?? $url;
    }

    /**
     * Moves the key of the email link into a cookie and continues on the Orchestra page.
     *
     * @internal Hooked to `login_form_rp` and `login_form_resetpass` of wp-login.php.
     */
    public static function continueOnPage(): void
    {
        $page = self::url(self::VIEW_RESET);
        $key = \is_string($_GET['key'] ?? null) ? \wp_unslash($_GET['key']) : '';
        $login = \is_string($_GET['login'] ?? null) ? \wp_unslash($_GET['login']) : '';
        if ($page === null || $key === '' || $login === '') {
            return;
        }

        \setcookie(self::COOKIE, $login . ':' . $key, self::cookieOptions(0));
        \wp_safe_redirect($page);
        exit;
    }

    /**
     * The view of the login page requested in the URL: {@see VIEW_LOST}, {@see VIEW_RESET} or "".
     */
    public static function requestedView(): string
    {
        $view = \is_string($_GET[self::VIEW] ?? null) ? \sanitize_key($_GET[self::VIEW]) : '';

        return \in_array($view, [self::VIEW_LOST, self::VIEW_RESET], true) ? $view : '';
    }

    /**
     * The message for the state in the URL, as [type, text], or null.
     *
     * @return array{string, string}|null
     */
    public static function stateMessage(): ?array
    {
        $state = \is_string($_GET[self::STATE] ?? null) ? \sanitize_key($_GET[self::STATE]) : '';

        return self::messageFor($state);
    }

    /**
     * @return array{string, string}|null
     */
    public static function messageFor(string $state): ?array
    {
        return match ($state) {
            'sent' => ['success', \__(
                'If there is an account with this name or email address, it has received an email with a link.',
                'orchestra-core'
            )],
            'done' => ['success', \__('Your new password has been set. You can log in with it now.', 'orchestra-core')],
            'invalid' => ['error', \__(
                'This link is invalid or has expired. Please ask for a new one.',
                'orchestra-core'
            )],
            'short' => ['error', \sprintf(
                /* translators: %d: minimum number of characters */
                \__('The new password must have at least %d characters.', 'orchestra-core'),
                self::MIN_LENGTH
            )],
            'mismatch' => ['error', \__('The new passwords do not match.', 'orchestra-core')],
            default => null,
        };
    }

    /**
     * Why a new password cannot be set ({@see messageFor()} state), or null if it can.
     */
    public static function passwordError(string $password, string $repeat): ?string
    {
        if (\mb_strlen($password) < self::MIN_LENGTH) {
            return 'short';
        }

        return $password === $repeat ? null : 'mismatch';
    }

    /**
     * The form for asking for the link.
     */
    public static function requestForm(): string
    {
        $back = self::url('') ?? \home_url('/');

        return '<h2 class="orchestra-card__title">' . \esc_html__('Forgot your password?', 'orchestra-core')
            . '</h2><p>' . \esc_html__(
                'Enter your username or email address. You will receive an email with a link to set a new password.',
                'orchestra-core'
            ) . '</p>'
            . Form::open(self::REQUEST_ACTION, self::REQUEST_ACTION, [], ['class' => 'orchestra-form'])
            . Html::field(
                'orchestra-lost-login',
                \__('Username or email address', 'orchestra-core'),
                '<input type="text" id="orchestra-lost-login" name="' . self::FIELD_LOGIN
                    . '" autocomplete="username" required>'
            )
            . '<div class="orchestra-actions">' . Form::submit(\__('Get a new password', 'orchestra-core'), 'primary')
            . Form::link($back, \__('Back to login', 'orchestra-core'), 'link') . '</div></form>';
    }

    /**
     * The form for setting the new password, or the message that the link is invalid.
     */
    public static function resetForm(): string
    {
        if (self::userFromCookie() === null) {
            $message = self::messageFor('invalid');

            $lost = self::url(self::VIEW_LOST) ?? \wp_lostpassword_url();

            return Notices::html('error', $message[1] ?? '') . '<p>'
                . Form::link($lost, \__('Get a new link', 'orchestra-core')) . '</p>';
        }

        $hint = \sprintf(
            /* translators: %d: minimum number of characters */
            \__('At least %d characters.', 'orchestra-core'),
            self::MIN_LENGTH
        );

        return '<h2 class="orchestra-card__title">' . \esc_html__('Set a new password', 'orchestra-core') . '</h2>'
            . Form::open(self::RESET_ACTION, self::RESET_ACTION, [], ['class' => 'orchestra-form'])
            . Html::field(
                'orchestra-new-password',
                \__('New password', 'orchestra-core'),
                '<input type="password" id="orchestra-new-password" name="' . self::FIELD_PASSWORD
                    . '" autocomplete="new-password" minlength="' . self::MIN_LENGTH . '" required>',
                $hint
            )
            . Html::field(
                'orchestra-new-password-repeat',
                \__('Repeat new password', 'orchestra-core'),
                '<input type="password" id="orchestra-new-password-repeat" name="' . self::FIELD_REPEAT
                    . '" autocomplete="new-password" minlength="' . self::MIN_LENGTH . '" required>'
            )
            . '<div class="orchestra-actions">' . Form::submit(\__('Set password', 'orchestra-core'), 'primary')
            . '</div></form>';
    }

    /**
     * @internal Hooked to `admin_post(_nopriv)_orchestra_core_lost_password`.
     */
    public static function handleRequest(): never
    {
        self::verifyNonce(self::REQUEST_ACTION);
        $login = \is_string($_POST[self::FIELD_LOGIN] ?? null)
            ? \trim(\sanitize_text_field(\wp_unslash($_POST[self::FIELD_LOGIN])))
            : '';
        if ($login !== '') {
            // The result is not shown, so that nobody can find out which accounts exist.
            \retrieve_password($login);
        }

        self::redirect(self::VIEW_LOST, 'sent');
    }

    /**
     * @internal Hooked to `admin_post(_nopriv)_orchestra_core_reset_password`.
     */
    public static function handleReset(): never
    {
        self::verifyNonce(self::RESET_ACTION);
        $user = self::userFromCookie();
        if ($user === null) {
            self::redirect(self::VIEW_RESET, 'invalid');
        }

        $password = \is_string($_POST[self::FIELD_PASSWORD] ?? null) ? \wp_unslash($_POST[self::FIELD_PASSWORD]) : '';
        $repeat = \is_string($_POST[self::FIELD_REPEAT] ?? null) ? \wp_unslash($_POST[self::FIELD_REPEAT]) : '';
        $error = self::passwordError($password, $repeat);
        if ($error !== null) {
            self::redirect(self::VIEW_RESET, $error);
        }

        \reset_password($user, $password);
        self::forgetCookie();
        self::redirect('', 'done');
    }

    private static function userFromCookie(): ?WP_User
    {
        $value = \is_string($_COOKIE[self::COOKIE] ?? null) ? \wp_unslash($_COOKIE[self::COOKIE]) : '';
        $separator = \strrpos($value, ':');
        if ($separator === false) {
            return null;
        }
        $user = \check_password_reset_key(\substr($value, $separator + 1), \substr($value, 0, $separator));

        return $user instanceof WP_User ? $user : null;
    }

    private static function forgetCookie(): void
    {
        \setcookie(self::COOKIE, '', self::cookieOptions(\time() - \YEAR_IN_SECONDS));
    }

    /**
     * The cookie is read by the Orchestra page and by admin-post.php, so it is valid for the whole
     * site (on its host only), and only sent over HTTPS when the site uses it.
     *
     * @return array{expires: int, path: string, secure: bool, httponly: bool, samesite: 'Lax'}
     */
    private static function cookieOptions(int $expires): array
    {
        $path = \wp_parse_url(\home_url('/'), \PHP_URL_PATH);

        return [
            'expires' => $expires,
            'path' => \is_string($path) && $path !== '' ? $path : '/',
            'secure' => \is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    private static function verifyNonce(string $action): void
    {
        $nonce = \is_string($_POST['_wpnonce'] ?? null) ? \sanitize_key($_POST['_wpnonce']) : '';
        if (\wp_verify_nonce($nonce, $action) === false) {
            \wp_die(\esc_html__('The form has expired. Please try again.', 'orchestra-core'), '', ['response' => 403]);
        }
    }

    private static function redirect(string $view, string $state): never
    {
        \nocache_headers();
        \wp_safe_redirect(self::url($view, [self::STATE => $state]) ?? \home_url('/'));
        exit;
    }
}
