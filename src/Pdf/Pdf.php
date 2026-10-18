<?php

declare(strict_types=1);

namespace Orchestra\Core\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDF documents for the Orchestra plugins, e.g. the assignments of a project or a seating plan.
 *
 * The plugins describe the content as simple HTML (headings, paragraphs, tables); this class adds
 * the page layout and renders it with dompdf. dompdf supports tables well, but neither flexbox
 * nor CSS grid, so layouts are built with tables.
 *
 *     Pdf::download(Pdf::document('Spring Concert', '<h2>Seating</h2><table>…</table>'), 'Seating.pdf');
 */
final class Pdf
{
    public const PORTRAIT = 'portrait';
    public const LANDSCAPE = 'landscape';

    /**
     * A complete HTML document with the base styles.
     *
     * @param string $body Escaped HTML
     * @param string $css Additional styles of the document
     */
    public static function document(string $title, string $body, string $css = ''): string
    {
        $dateFormat = \get_option('date_format');
        $footer = \sprintf(
            /* translators: %s: date */
            \__('Created on %s', 'orchestra-core'),
            \wp_date(\is_string($dateFormat) && $dateFormat !== '' ? $dateFormat : 'Y-m-d')
        );

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . \esc_html($title) . '</title><style>'
            . '@page { margin: 14mm 12mm 16mm; }'
            . 'body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: #1d1d1f; }'
            . 'h1 { font-size: 16pt; margin: 0 0 2mm; }'
            . 'h2 { font-size: 12pt; margin: 6mm 0 2mm; page-break-after: avoid; }'
            . 'p { margin: 0 0 2mm; }'
            . '.muted { color: #6b6b6b; }'
            . 'table { border-collapse: collapse; width: 100%; }'
            . 'th, td { border: 0.6pt solid #b8b8b8; padding: 1.6mm 2mm; text-align: left; vertical-align: top; }'
            . 'th { background: #f0f0f0; font-weight: bold; }'
            . 'tr { page-break-inside: avoid; }'
            . '.footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7.5pt; color: #6b6b6b; }'
            . $css
            . '</style></head><body>'
            . '<div class="footer">' . \esc_html($footer) . '</div>'
            . '<h1>' . \esc_html($title) . '</h1>' . $body
            . '</body></html>';
    }

    /**
     * Renders the HTML to a PDF.
     *
     * @param string $orientation {@see PORTRAIT} or {@see LANDSCAPE}
     * @return string The PDF
     */
    public static function render(string $html, string $orientation = self::PORTRAIT): string
    {
        $options = new Options();
        // Only the HTML of the plugins is rendered: no remote files, no PHP, no access to local files.
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setIsJavascriptEnabled(false);
        $options->setChroot([\dirname(__DIR__, 2) . '/vendor/dompdf/dompdf']);
        // The bundled fonts need no cache; others would be cached in the temporary directory.
        $options->setFontCache(\get_temp_dir());
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $orientation === self::LANDSCAPE ? self::LANDSCAPE : self::PORTRAIT);
        $dompdf->render();
        self::addPageNumbers($dompdf);

        return (string) $dompdf->output();
    }

    /**
     * Sends the PDF as a download and ends the request.
     */
    public static function download(string $html, string $fileName, string $orientation = self::PORTRAIT): never
    {
        $pdf = self::render($html, $orientation);

        \nocache_headers();
        \header('Content-Type: application/pdf');
        \header('Content-Length: ' . (string) \strlen($pdf));
        \header('Content-Disposition: ' . self::attachment($fileName));
        \header('X-Content-Type-Options: nosniff');
        \header('Cache-Control: private, no-store, max-age=0');
        echo $pdf; // A file download, not HTML.
        exit;
    }

    /**
     * A file name from a title, e.g. "Spring Concert 2027 - Seating.pdf".
     */
    public static function fileName(string ...$parts): string
    {
        $name = \trim((string) \preg_replace('/[\\\\\/:*?"<>|\r\n]+/', ' ', \implode(' - ', $parts)));

        return ($name === '' ? 'document' : $name) . '.pdf';
    }

    /**
     * An `attachment` header value that works for non-ASCII file names (RFC 6266).
     */
    public static function attachment(string $fileName): string
    {
        $fileName = \str_replace(["\r", "\n", '/', '\\'], ['', '', '-', '-'], $fileName);
        $fallback = \str_replace(['"', '%'], '_', (string) \preg_replace('/[^\x20-\x7E]/u', '_', $fileName));

        return \sprintf('attachment; filename="%s"; filename*=UTF-8\'\'%s', $fallback, \rawurlencode($fileName));
    }

    private static function addPageNumbers(Dompdf $dompdf): void
    {
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans') ?? 'Helvetica';
        // {PAGE_NUM} and {PAGE_COUNT} are replaced by dompdf; keep them in translations.
        $text = \__('Page {PAGE_NUM} of {PAGE_COUNT}', 'orchestra-core');
        $points = 72 / 25.4;
        $canvas->page_text(
            $canvas->get_width() - 34 * $points,
            $canvas->get_height() - 10 * $points,
            $text,
            $font,
            7.5,
            [0.42, 0.42, 0.42]
        );
    }
}
