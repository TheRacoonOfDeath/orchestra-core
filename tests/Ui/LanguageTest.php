<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Ui;

use Orchestra\Core\Tests\Fakes\WordPress;
use Orchestra\Core\Ui\Language;
use PHPUnit\Framework\TestCase;

final class LanguageTest extends TestCase
{
    private const DIRECTORY = '/wp-content/plugins/orchestra-projects/languages';

    protected function setUp(): void
    {
        WordPress::reset();
    }

    public function testFollowsTheSiteLanguageByDefault(): void
    {
        $this->assertSame('en_US', Language::locale('en_US'));
        $this->assertSame('fr_FR', Language::locale('fr_FR'));
    }

    public function testUsesGermanForEveryGermanVariant(): void
    {
        foreach (['de_DE', 'de_DE_formal', 'de_AT', 'de_CH', 'de_CH_informal'] as $siteLocale) {
            $this->assertSame('de_DE', Language::locale($siteLocale), $siteLocale);
        }
    }

    public function testTheChosenLanguageOverridesTheSiteLanguage(): void
    {
        WordPress::$options[Language::OPTION] = Language::GERMAN;
        $this->assertSame('de_DE', Language::locale('en_US'));

        WordPress::$options[Language::OPTION] = Language::ENGLISH;
        $this->assertSame('en_US', Language::locale('de_DE'));
    }

    public function testLoadsTheTranslationFileOfTheChosenLanguage(): void
    {
        WordPress::$options[Language::OPTION] = Language::GERMAN;

        $this->assertSame(
            self::DIRECTORY . '/orchestra-projects-de_DE.mo',
            Language::translationFile(self::DIRECTORY . '/orchestra-projects-en_GB.mo', 'orchestra-projects')
        );
    }

    public function testLeavesTheTranslationsOfOtherPluginsAlone(): void
    {
        WordPress::$options[Language::OPTION] = Language::GERMAN;

        $this->assertSame(
            '/wp-content/languages/plugins/woocommerce-en_GB.mo',
            Language::translationFile('/wp-content/languages/plugins/woocommerce-en_GB.mo', 'woocommerce')
        );
        $this->assertSame('/languages/', Language::translationDirectory('/languages/', 'woocommerce', 'de_DE'));
    }

    public function testEnglishKeepsWordPressFromLoadingTheSiteLanguageJustInTime(): void
    {
        $this->assertSame('/languages/', Language::translationDirectory('/languages/', 'orchestra-core', 'de_DE'));

        WordPress::$options[Language::OPTION] = Language::ENGLISH;
        $this->assertFalse(Language::translationDirectory('/languages/', 'orchestra-core', 'de_DE'));
    }

    public function testOnlyAcceptsOfferedLanguages(): void
    {
        $this->assertSame('de_DE', Language::sanitize('de_DE'));
        $this->assertSame('', Language::sanitize('xx_XX'));
        $this->assertSame('', Language::sanitize(['de_DE']));
    }
}
