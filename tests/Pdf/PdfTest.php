<?php

declare(strict_types=1);

namespace Orchestra\Core\Tests\Pdf;

use Orchestra\Core\Pdf\Pdf;
use PHPUnit\Framework\TestCase;

final class PdfTest extends TestCase
{
    public function testRendersADocumentWithUmlauts(): void
    {
        $html = Pdf::document('Frühjahrskonzert', '<table><tr><th>Stimme</th><td>Müller, Jürgen</td></tr></table>');

        $pdf = Pdf::render($html, Pdf::LANDSCAPE);

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('<title>Frühjahrskonzert</title>', $html);
        // Landscape A4 is 842 x 595 points.
        $this->assertMatchesRegularExpression('/MediaBox \[0(\.0+)? 0(\.0+)? 841\.8\d* 595\.2\d*\]/', $pdf);
    }

    public function testBuildsSafeFileNames(): void
    {
        $this->assertSame('Spring Concert 2027 - Seating.pdf', Pdf::fileName('Spring Concert 2027', 'Seating'));
        $this->assertSame('A B - C.pdf', Pdf::fileName('A/B', 'C'));
        $this->assertSame(
            'attachment; filename="Fr_hling.pdf"; filename*=UTF-8\'\'Fr%C3%BChling.pdf',
            Pdf::attachment('Frühling.pdf')
        );
    }
}
