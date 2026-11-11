<?php

declare(strict_types=1);

namespace Orchestra\Core\Privacy;

/**
 * Suggests a text for the privacy policy (Settings → Privacy → Policy guide) about what
 * orchestra-core itself does: the login, its cookie and browser storage, the internal pages and
 * the update check. The domain plugins add texts about their data. See docs/gdpr.md.
 */
final class PolicyText
{
    public static function register(): void
    {
        \add_action('admin_init', [self::class, 'add']);
    }

    /**
     * @internal Hooked to `admin_init`.
     */
    public static function add(): void
    {
        if (!\function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $paragraphs = [
            \__(
                'The orchestra pages are an internal area for members. Accounts are created by the orchestra; '
                . 'there is no self-registration. The pages are hidden from search engines, and the profiles of '
                . 'members cannot be found from outside.',
                'orchestra-core'
            ),
            \__(
                'When you log in, WordPress sets the cookies needed for the login. When you open the link to set a '
                . 'new password, a cookie with your username and the key of the link is set until you have set the '
                . 'password or close the browser. After a form is sent, the browser keeps the scroll position for '
                . 'one minute in its session storage. These are needed for the pages to work; no tracking or '
                . 'analytics takes place.',
                'orchestra-core'
            ),
            \__(
                'Failed logins and requests for a new password do not show whether an account exists. An account '
                . 'receives at most one email with a new password link every few minutes.',
                'orchestra-core'
            ),
            \__(
                'Twice a day, the website asks GitHub (GitHub Inc., USA) whether there are updates of the orchestra '
                . 'plugins. The request contains the address of the website and the versions of WordPress, PHP and '
                . 'the plugins, but no data of members.',
                'orchestra-core'
            ),
        ];
        $text = '';
        foreach ($paragraphs as $paragraph) {
            $text .= '<p>' . \esc_html($paragraph) . '</p>';
        }

        \wp_add_privacy_policy_content(\__('Orchestra', 'orchestra-core'), \wp_kses_post($text));
    }
}
