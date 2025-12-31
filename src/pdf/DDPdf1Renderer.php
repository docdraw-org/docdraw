<?php

declare(strict_types=1);

namespace DocDraw\Pdf;

use FPDF;

final class DDPdf1Renderer
{
    // Page + typography defaults (DD-PDF-1 draft)
    private const MARGIN_PT = 72.0; // 1"
    private const BODY_SIZE_PT = 11.0;
    private const LINE_HEIGHT_PT = 13.75;
    private const U_PT = 6.0;

    private const LIST_INDENT_STEP_PT = 18.0;
    private const BULLET_COL_W_PT = 18.0;

    public function render(string $docdrawText, string $outPath): void
    {
        $pdf = new FPDF('P', 'pt', 'Letter');
        $pdf->SetAutoPageBreak(true, self::MARGIN_PT);
        $pdf->SetMargins(self::MARGIN_PT, self::MARGIN_PT, self::MARGIN_PT);
        $pdf->AddPage();

        // Metadata (try to keep stable)
        $pdf->SetCreator('DocDraw (DD-PDF-1)');
        $pdf->SetAuthor('DocDraw');
        $pdf->SetTitle('DocDraw Document');

        $pdf->SetTextColor(0x11, 0x11, 0x11);
        $pdf->SetFont('Helvetica', '', self::BODY_SIZE_PT);

        $lines = $this->splitLines($docdrawText);

        $inPBlock = false;
        $pBuf = [];
        $inCode = false;
        $codeBuf = [];

        $orderedCounters = []; // per level
        $orderedActive = false;

        $currentListContext = null; // ['textX'=>float,'maxW'=>float]

        foreach ($lines as $lineRaw) {
            $line = rtrim($lineRaw, " \t");
            $trim = trim($line);

            if ($inCode) {
                if ($trim === '}') {
                    $this->renderCodeBlock($pdf, $codeBuf);
                    $codeBuf = [];
                    $inCode = false;
                    $currentListContext = null;
                    $orderedActive = false;
                    continue;
                }
                $codeBuf[] = $lineRaw;
                continue;
            }

            if ($inPBlock) {
                if ($trim === '}') {
                    $this->renderParagraph($pdf, implode("\n", $pBuf), true);
                    $pBuf = [];
                    $inPBlock = false;
                    $currentListContext = null;
                    $orderedActive = false;
                    continue;
                }
                $pBuf[] = $trim;
                continue;
            }

            if ($trim === '') {
                // Blank line: reset list continuity
                $currentListContext = null;
                $orderedActive = false;
                $orderedCounters = [];
                continue;
            }

            if ($trim === 'p{') {
                $inPBlock = true;
                $pBuf = [];
                continue;
            }

            if ($trim === 'code{') {
                $inCode = true;
                $codeBuf = [];
                continue;
            }

            if ($trim === '---') {
                $this->renderDivider($pdf);
                $currentListContext = null;
                $orderedActive = false;
                $orderedCounters = [];
                continue;
            }

            if (preg_match('/^#([1-6]):\s+(.+)$/', $trim, $m)) {
                $level = (int)$m[1];
                $text = $m[2];
                $this->renderHeading($pdf, $level, $text);
                $currentListContext = null;
                $orderedActive = false;
                $orderedCounters = [];
                continue;
            }

            if (preg_match('/^p:\s+(.+)$/', $trim, $m)) {
                $this->renderParagraph($pdf, $m[1], false);
                $currentListContext = null;
                $orderedActive = false;
                $orderedCounters = [];
                continue;
            }

            if (preg_match('/^q:\s+(.+)$/', $trim, $m)) {
                $this->renderQuote($pdf, $m[1]);
                $currentListContext = null;
                $orderedActive = false;
                $orderedCounters = [];
                continue;
            }

            if (preg_match('/^\.\.:\s+(.+)$/', $trim, $m)) {
                if ($currentListContext) {
                    $this->renderListTextLines($pdf, (string)$m[1], $currentListContext['textX'], $currentListContext['maxW'], false);
                } else {
                    // If invalid structure slips through, treat as paragraph.
                    $this->renderParagraph($pdf, $m[1], false);
                }
                continue;
            }

            // Bullet list item
            if (preg_match('/^-([1-9]):\s+(.+)$/', $trim, $m)) {
                $lvl = (int)$m[1];
                $text = $m[2];
                $orderedActive = false;
                $orderedCounters = [];
                $currentListContext = $this->renderBulletItem($pdf, $lvl, $text);
                continue;
            }

            // Ordered list item
            if (preg_match('/^1-([1-9]):\s+(.+)$/', $trim, $m)) {
                $lvl = (int)$m[1];
                $text = $m[2];
                if (!$orderedActive) {
                    $orderedActive = true;
                    $orderedCounters = [];
                }
                $currentListContext = $this->renderOrderedItem($pdf, $lvl, $text, $orderedCounters);
                $orderedCounters = $currentListContext['counters'];
                unset($currentListContext['counters']);
                continue;
            }

            // Fallback: treat as paragraph
            $this->renderParagraph($pdf, $trim, false);
            $currentListContext = null;
            $orderedActive = false;
            $orderedCounters = [];
        }

        $pdf->Output('F', $outPath);
    }

    private function splitLines(string $s): array
    {
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        return explode("\n", $s);
    }

    private function ensureRoom(FPDF $pdf, float $neededPt): void
    {
        $pageH = $pdf->GetPageHeight();
        $bottom = $pageH - self::MARGIN_PT;
        if ($pdf->GetY() + $neededPt > $bottom) {
            $pdf->AddPage();
        }
    }

    private function renderHeading(FPDF $pdf, int $level, string $text): void
    {
        $sizes = [1 => 18.0, 2 => 14.0, 3 => 12.0, 4 => 11.0, 5 => 11.0, 6 => 11.0];
        $size = $sizes[$level] ?? 12.0;
        $spaceBefore = ($level === 1) ? 18.0 : 12.0;
        $spaceAfter = 6.0;

        // Keep-with-next (space for heading + at least 1 following line)
        $this->ensureRoom($pdf, $spaceBefore + $size + self::LINE_HEIGHT_PT);
        $pdf->Ln($spaceBefore);

        $pdf->SetFont('Helvetica', 'B', $size);
        $pdf->MultiCell(0, $size + 2.0, $text, 0, 'L');
        $pdf->SetFont('Helvetica', '', self::BODY_SIZE_PT);
        $pdf->Ln($spaceAfter);
    }

    private function renderParagraph(FPDF $pdf, string $text, bool $preserveNewlines): void
    {
        $this->ensureRoom($pdf, self::LINE_HEIGHT_PT * 2);
        $maxW = $pdf->GetPageWidth() - (self::MARGIN_PT * 2);
        $x = self::MARGIN_PT;

        $chunks = $preserveNewlines ? explode("\n", $text) : [trim(preg_replace('/\s+/', ' ', $text) ?? $text)];
        foreach ($chunks as $i => $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') continue;
            $lines = $this->wrapText($pdf, $chunk, $maxW);
            foreach ($lines as $ln) {
                $this->ensureRoom($pdf, self::LINE_HEIGHT_PT);
                $pdf->SetX($x);
                $pdf->Cell($maxW, self::LINE_HEIGHT_PT, $ln, 0, 1, 'L');
            }
            if ($preserveNewlines && $i < count($chunks) - 1) {
                // explicit line break within paragraph
            }
        }
        $pdf->Ln(self::U_PT);
    }

    private function renderDivider(FPDF $pdf): void
    {
        $pdf->Ln(self::U_PT * 2);
        $this->ensureRoom($pdf, self::U_PT * 4);
        $x1 = self::MARGIN_PT;
        $x2 = $pdf->GetPageWidth() - self::MARGIN_PT;
        $y = $pdf->GetY();
        $pdf->SetDrawColor(0xBB, 0xBB, 0xBB);
        $pdf->SetLineWidth(0.5);
        $pdf->Line($x1, $y, $x2, $y);
        $pdf->Ln(self::U_PT * 2);
        $pdf->SetDrawColor(0, 0, 0);
    }

    private function renderQuote(FPDF $pdf, string $text): void
    {
        $indent = 18.0;
        $ruleW = 2.0;
        $gap = 8.0;
        $pdf->Ln(self::U_PT);

        $maxW = $pdf->GetPageWidth() - (self::MARGIN_PT * 2) - $indent;
        $x = self::MARGIN_PT + $indent;
        $yStart = $pdf->GetY();

        $lines = $this->wrapText($pdf, $text, $maxW);
        foreach ($lines as $ln) {
            $this->ensureRoom($pdf, self::LINE_HEIGHT_PT);
            $pdf->SetX($x);
            $pdf->Cell($maxW, self::LINE_HEIGHT_PT, $ln, 0, 1, 'L');
        }

        // draw left rule
        $yEnd = $pdf->GetY();
        $pdf->SetDrawColor(0xDD, 0xDD, 0xDD);
        $pdf->SetLineWidth($ruleW);
        $pdf->Line(self::MARGIN_PT + ($indent - $gap), $yStart, self::MARGIN_PT + ($indent - $gap), $yEnd);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Ln(self::U_PT);
    }

    /**
     * @return array{textX:float,maxW:float}
     */
    private function renderBulletItem(FPDF $pdf, int $level, string $text): array
    {
        $baseIndent = ($level - 1) * self::LIST_INDENT_STEP_PT;
        $markerX = self::MARGIN_PT + $baseIndent;
        $textX = $markerX + self::BULLET_COL_W_PT;
        $maxW = $pdf->GetPageWidth() - self::MARGIN_PT - $textX;

        $marker = $this->bulletGlyph($level);
        $this->ensureRoom($pdf, self::LINE_HEIGHT_PT);

        // marker
        $pdf->SetX($markerX);
        $pdf->Cell(self::BULLET_COL_W_PT, self::LINE_HEIGHT_PT, $marker, 0, 0, 'R');

        // text
        $this->renderListTextLines($pdf, $text, $textX, $maxW, true);

        // item spacing (2pt)
        $pdf->Ln(2.0);
        return ['textX' => $textX, 'maxW' => $maxW];
    }

    private function bulletGlyph(int $level): string
    {
        if ($level === 1) return '•';
        if ($level === 2) return '◦';
        return '▪';
    }

    /**
     * @param array<int,int> $counters
     * @return array{textX:float,maxW:float,counters:array<int,int>}
     */
    private function renderOrderedItem(FPDF $pdf, int $level, string $text, array $counters): array
    {
        // counter per level, reset deeper levels
        for ($i = $level + 1; $i <= 9; $i++) {
            unset($counters[$i]);
        }
        $counters[$level] = ($counters[$level] ?? 0) + 1;

        $marker = $counters[$level] . '.';

        $baseIndent = ($level - 1) * self::LIST_INDENT_STEP_PT;
        $markerX = self::MARGIN_PT + $baseIndent;
        $textX = $markerX + self::BULLET_COL_W_PT;
        $maxW = $pdf->GetPageWidth() - self::MARGIN_PT - $textX;

        $this->ensureRoom($pdf, self::LINE_HEIGHT_PT);

        $pdf->SetX($markerX);
        $pdf->Cell(self::BULLET_COL_W_PT, self::LINE_HEIGHT_PT, $marker, 0, 0, 'R');

        $this->renderListTextLines($pdf, $text, $textX, $maxW, true);
        $pdf->Ln(2.0);

        return ['textX' => $textX, 'maxW' => $maxW, 'counters' => $counters];
    }

    private function renderListTextLines(FPDF $pdf, string $text, float $textX, float $maxW, bool $firstLineNewRow): void
    {
        $lines = $this->wrapText($pdf, $text, $maxW);
        foreach ($lines as $idx => $ln) {
            $this->ensureRoom($pdf, self::LINE_HEIGHT_PT);
            $pdf->SetX($textX);
            if ($idx === 0 && $firstLineNewRow) {
                $pdf->Cell($maxW, self::LINE_HEIGHT_PT, $ln, 0, 1, 'L');
            } else {
                // continuation: new line
                $pdf->Cell($maxW, self::LINE_HEIGHT_PT, $ln, 0, 1, 'L');
            }
        }
    }

    private function renderCodeBlock(FPDF $pdf, array $lines): void
    {
        $pdf->Ln(self::U_PT);
        $maxW = $pdf->GetPageWidth() - (self::MARGIN_PT * 2);
        $x = self::MARGIN_PT;

        $pdf->SetFont('Courier', '', 10.0);
        $pdf->SetFillColor(0xF6, 0xF6, 0xF6);
        $pdf->SetDrawColor(0xE0, 0xE0, 0xE0);

        // Simple boxed block (no syntax highlight)
        $yStart = $pdf->GetY();
        foreach ($lines as $ln) {
            $this->ensureRoom($pdf, self::LINE_HEIGHT_PT);
            $pdf->SetX($x);
            $pdf->MultiCell($maxW, self::LINE_HEIGHT_PT, rtrim($ln, "\r\n"), 0, 'L', true);
        }
        $yEnd = $pdf->GetY();

        // border rectangle
        $pdf->Rect($x, $yStart, $maxW, $yEnd - $yStart);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', self::BODY_SIZE_PT);
        $pdf->Ln(self::U_PT);
    }

    /**
     * @return string[]
     */
    private function wrapText(FPDF $pdf, string $text, float $maxW): array
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);
        if ($text === '') return [''];

        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $cur = '';

        foreach ($words as $w) {
            $test = $cur === '' ? $w : ($cur . ' ' . $w);
            if ($pdf->GetStringWidth($test) <= $maxW) {
                $cur = $test;
                continue;
            }
            if ($cur !== '') {
                $lines[] = $cur;
            }
            // single long word
            if ($pdf->GetStringWidth($w) > $maxW) {
                $lines[] = $w;
                $cur = '';
            } else {
                $cur = $w;
            }
        }
        if ($cur !== '') $lines[] = $cur;
        return $lines ?: [''];
    }
}


