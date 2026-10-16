<?php

declare(strict_types=1);

namespace Orchestra\Core\Ui;

/**
 * The language of all Orchestra plugins, chosen in Settings → General independently of the site
 * language.
 *
 * Every Orchestra plugin loads the translations of its text domain (`orchestra-*`) from its
 * `languages` directory. This class only decides which file is loaded: it replaces the locale in
 * the file name, also for translations that WordPress loads just in time. The translations are
 * still registered for the current locale, so WordPress finds them.
 */
final class Language
{
    public const OPTION = 'orchestra_core_language';

    /**
     * Follow the site language; German variants (formal, Austria, Switzerland) use German.
     */
    public const SITE = '';
    public const ENGLISH = 'en_US';
    public const GERMAN = 'de_DE';

    private const DOMAIN_PREFIX = 'orchestra-';

    public static function register(): void
    {
        \add_filter('load_textdomain_mofile', [self::class, 'translationFile'], 10, 2);
        \add_filter('lang_dir_for_domain', [self::class, 'translationDirectory'], 10, 3);
        \add_action('admin_init', [self::class, 'registerSetting']);
    }

    /**
     * @return array<string, string> setting value => language name
     */
    public static function choices(): array
    {
        return [
            self::SITE => \__('Site language', 'orchestra-core'),
            // Language names are shown in their own language, so everyone finds theirs.
            self::ENGLISH => 'English',
            self::GERMAN => 'Deutsch',
        ];
    }

    /**
     * The locale whose translations the Orchestra plugins use.
     */
    public static function locale(string $siteLocale): string
    {
        $setting = \get_option(self::OPTION, self::SITE);
        if ($setting === self::ENGLISH || $setting === self::GERMAN) {
            return $setting;
        }

        return \str_starts_with($siteLocale, 'de_') || $siteLocale === 'de' ? self::GERMAN : $siteLocale;
    }

    /**
     * @internal Hooked to `load_textdomain_mofile`.
     */
    public static function translationFile(string $file, string $domain): string
    {
        if (!self::isOrchestraDomain($domain)) {
            return $file;
        }
        $prefix = $domain . '-';
        $name = \basename($file, '.mo');
        if (!\str_starts_with($name, $prefix)) {
            return $file;
        }

        $locale = self::locale(\substr($name, \strlen($prefix)));

        return \dirname($file) . '/' . $prefix . $locale . '.mo';
    }

    /**
     * Keeps WordPress from loading the translations of the site language just in time when the
     * Orchestra plugins are set to English.
     *
     * @internal Hooked to `lang_dir_for_domain`.
     */
    public static function translationDirectory(string|false $path, string $domain, string $locale): string|false
    {
        return self::isOrchestraDomain($domain) && self::locale($locale) === self::ENGLISH ? false : $path;
    }

    /**
     * @internal Hooked to `admin_init`.
     */
    public static function registerSetting(): void
    {
        \register_setting('general', self::OPTION, [
            'type' => 'string',
            'default' => self::SITE,
            'sanitize_callback' => [self::class, 'sanitize'],
        ]);
        \add_settings_field(
            self::OPTION,
            \__('Orchestra language', 'orchestra-core'),
            [self::class, 'renderField'],
            'general',
            'default',
            ['label_for' => self::OPTION]
        );
    }

    /**
     * @internal Sanitize callback of the setting.
     */
    public static function sanitize(mixed $value): string
    {
        return \is_string($value) && \array_key_exists($value, self::choices()) ? $value : self::SITE;
    }

    /**
     * @internal Settings field callback.
     */
    public static function renderField(): void
    {
        $current = self::sanitize(\get_option(self::OPTION, self::SITE));
        $options = '';
        foreach (self::choices() as $value => $label) {
            $options .= '<option value="' . \esc_attr($value) . '"' . \selected($current, $value, false) . '>'
                . \esc_html($label) . '</option>';
        }

        echo '<select id="' . \esc_attr(self::OPTION) . '" name="' . \esc_attr(self::OPTION) . '">' . $options
            . '</select><p class="description">'
            . \esc_html__(
                'The language of the Orchestra pages and screens. WordPress itself keeps the site language.',
                'orchestra-core'
            ) . '</p>'; // escaped above
    }

    private static function isOrchestraDomain(string $domain): bool
    {
        return \str_starts_with($domain, self::DOMAIN_PREFIX);
    }
}
