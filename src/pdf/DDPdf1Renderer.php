<?php

declare(strict_types=1);

namespace DocDraw\Pdf;

use FPDF;

/**
 * FPDF sets CreationDate to current time inside its internal _enddoc(), which makes output non-deterministic.
 * This subclass overrides _enddoc() to use a fixed CreationDate.
 */
final class DeterministicFPDF extends FPDF
{
    // Copy of FPDF::_enddoc() with one change: CreationDate is fixed (0).
    protected function _enddoc(): void
    {
        $this->CreationDate = 0;
        $this->_putheader();
        $this->_putpages();
        $this->_putresources();
        // Info
        $this->_newobj();
        $this->_put('<<');
        $this->_putinfo();
        $this->_put('>>');
        $this->_put('endobj');
        // Catalog
        $this->_newobj();
        $this->_put('<<');
        $this->_putcatalog();
        $this->_put('>>');
        $this->_put('endobj');
        // Cross-ref
        $offset = $this->_getoffset();
        $this->_put('xref');
        $this->_put('0 '.($this->n + 1));
        $this->_put('0000000000 65535 f ');
        for ($i = 1; $i <= $this->n; $i++) {
            $this->_put(sprintf('%010d 00000 n ', $this->offsets[$i]));
        }
        // Trailer
        $this->_put('trailer');
        $this->_put('<<');
        $this->_puttrailer();
        $this->_put('>>');
        $this->_put('startxref');
        $this->_put($offset);
        $this->_put('%%EOF');
        $this->state = 3;
    }
}

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
        $pdf = new DeterministicFPDF('P', 'pt', 'Letter');
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

    /**
     * Parse DocDraw inline text into styled runs. This is intentionally strict but "safe":
     * if parsing fails (should be prevented by validation), we fall back to rendering raw text.
     *
     * Supported:
     * - **bold**
     * - *italic*
     * - ++underline++
     * - `code`
     *
     * @return array<int,array{text:string,font:string,style:string}>
     */
    private function parseInlineRunsSafe(string $text, string $baseFont, string $baseStyle): array
    {
        $len = strlen($text);
        $i = 0;

        $runs = [];
        $buf = '';
        $bufFont = $baseFont;
        $bufStyle = $baseStyle;

        $flush = function () use (&$runs, &$buf, &$bufFont, &$bufStyle): void {
            if ($buf === '') return;
            $runs[] = ['text' => $buf, 'font' => $bufFont, 'style' => $bufStyle];
            $buf = '';
        };

        $isEscapable = static function (string $ch): bool {
            return $ch === '\\' || $ch === '*' || $ch === '+' || $ch === '`';
        };

        $isMarkerAt = static function (string $s, int $idx): ?string {
            $two = substr($s, $idx, 2);
            if ($two === '**' || $two === '++') return $two;
            $one = $s[$idx] ?? '';
            if ($one === '*' || $one === '`') return $one;
            return null;
        };

        try {
            while ($i < $len) {
                $ch = $text[$i];
                if ($ch === '\\' && ($i + 1) < $len) {
                    $n = $text[$i + 1];
                    if ($isEscapable($n)) {
                        $buf .= $n;
                        $i += 2;
                        continue;
                    }
                }

                $marker = null;
                $two = substr($text, $i, 2);
                if ($two === '**' || $two === '++') {
                    $marker = $two;
                } elseif ($ch === '*' || $ch === '`') {
                    $marker = $ch;
                }

                if ($marker === null) {
                    $buf .= $ch;
                    $i++;
                    continue;
                }

                $mLen = strlen($marker);
                $flush();
                $i += $mLen;

                // scan for close; nesting disallowed (must be escaped)
                $content = '';
                while ($i < $len) {
                    $ch2 = $text[$i];
                    if ($ch2 === '\\' && ($i + 1) < $len) {
                        $n2 = $text[$i + 1];
                        if ($isEscapable($n2)) {
                            $content .= $n2;
                            $i += 2;
                            continue;
                        }
                    }
                    if ($mLen === 2) {
                        if (substr($text, $i, 2) === $marker) {
                            $i += 2;
                            break;
                        }
                    } else {
                        if ($ch2 === $marker) {
                            $i += 1;
                            break;
                        }
                    }
                    $other = $isMarkerAt($text, $i);
                    if ($other !== null) {
                        throw new \RuntimeException('Inline nesting not allowed');
                    }
                    $content .= $ch2;
                    $i++;
                }

                if ($content === '') {
                    throw new \RuntimeException('Empty/unclosed inline span');
                }

                $font = $baseFont;
                $style = $baseStyle;
                if ($marker === '**') {
                    $style = $this->mergeStyle($baseStyle, 'B');
                } elseif ($marker === '*') {
                    $style = $this->mergeStyle($baseStyle, 'I');
                } elseif ($marker === '++') {
                    $style = $this->mergeStyle($baseStyle, 'U');
                } elseif ($marker === '`') {
                    $font = 'Courier';
                    $style = '';
                }

                $runs[] = ['text' => $content, 'font' => $font, 'style' => $style];

                // reset buffer style
                $bufFont = $baseFont;
                $bufStyle = $baseStyle;
            }
        } catch (\Throwable $t) {
            // Fallback: render raw text deterministically.
            return [['text' => $text, 'font' => $baseFont, 'style' => $baseStyle]];
        }

        $flush();
        return $runs ?: [['text' => $text, 'font' => $baseFont, 'style' => $baseStyle]];
    }

    private function mergeStyle(string $base, string $add): string
    {
        $all = $base . $add;
        $out = '';
        foreach (['B', 'I', 'U'] as $flag) {
            if (str_contains($all, $flag)) $out .= $flag;
        }
        return $out;
    }

    /**
     * @param array<int,array{text:string,font:string,style:string}> $runs
     * @return array<int,array<int,array{text:string,font:string,style:string}>>
     */
    private function wrapInlineRuns(FPDF $pdf, array $runs, float $fontSizePt, float $maxW): array
    {
        // Tokenize into words/spaces (collapse any whitespace run into a single space).
        $tokens = [];
        foreach ($runs as $r) {
            $parts = preg_split('/(\s+)/', $r['text'], -1, PREG_SPLIT_DELIM_CAPTURE);
            if (!$parts) continue;
            foreach ($parts as $p) {
                if ($p === '') continue;
                $isSpace = preg_match('/^\s+$/', $p) === 1;
                $tokens[] = [
                    'text' => $isSpace ? ' ' : $p,
                    'font' => $r['font'],
                    'style' => $r['style'],
                    'isSpace' => $isSpace,
                ];
            }
        }

        $lines = [];
        $cur = [];
        $curW = 0.0;

        $measure = function (array $tok) use ($pdf, $fontSizePt): float {
            $pdf->SetFont($tok['font'], $tok['style'], $fontSizePt);
            return (float)$pdf->GetStringWidth($this->pdfText((string)$tok['text']));
        };

        $flushLine = function () use (&$lines, &$cur): void {
            // trim trailing spaces
            while ($cur && ($cur[count($cur) - 1]['isSpace'] ?? false)) array_pop($cur);
            if ($cur) $lines[] = $cur;
            $cur = [];
        };

        foreach ($tokens as $tok) {
            if (($tok['isSpace'] ?? false) && !$cur) {
                continue; // no leading spaces
            }
            $w = $measure($tok);
            if (!$cur) {
                $cur[] = $tok;
                $curW = $w;
                continue;
            }
            if (($tok['isSpace'] ?? false)) {
                if ($curW + $w <= $maxW) {
                    $cur[] = $tok;
                    $curW += $w;
                } else {
                    $flushLine();
                    $curW = 0.0;
                }
                continue;
            }
            if ($curW + $w <= $maxW) {
                $cur[] = $tok;
                $curW += $w;
                continue;
            }
            // new line
            $flushLine();
            $cur[] = $tok;
            $curW = $w;
        }
        $flushLine();
        return $lines ?: [[]];
    }

    /**
     * Render a single line of inline tokens.
     * @param array<int,array{text:string,font:string,style:string,isSpace?:bool}> $line
     */
    private function renderInlineLine(FPDF $pdf, array $line, float $x, float $maxW, float $lineH, float $fontSizePt): void
    {
        $pdf->SetX($x);
        foreach ($line as $tok) {
            $pdf->SetFont($tok['font'], $tok['style'], $fontSizePt);
            $txt = $this->pdfText((string)$tok['text']);
            $w = (float)$pdf->GetStringWidth($txt);
            $pdf->Cell($w, $lineH, $txt, 0, 0, 'L');
        }
        $pdf->Ln($lineH);
    }

    private function renderInlineTextBlock(FPDF $pdf, string $text, float $x, float $maxW, float $fontSizePt, float $lineH, string $baseFont, string $baseStyle): void
    {
        $runs = $this->parseInlineRunsSafe($text, $baseFont, $baseStyle);
        $wrapped = $this->wrapInlineRuns($pdf, $runs, $fontSizePt, $maxW);
        foreach ($wrapped as $line) {
            if (!$line) continue;
            $this->ensureRoom($pdf, $lineH);
            $this->renderInlineLine($pdf, $line, $x, $maxW, $lineH, $fontSizePt);
        }
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

        $maxW = $pdf->GetPageWidth() - (self::MARGIN_PT * 2);
        $x = self::MARGIN_PT;
        $this->renderInlineTextBlock($pdf, $text, $x, $maxW, $size, $size + 2.0, 'Helvetica', 'B');
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
            $this->renderInlineTextBlock($pdf, $chunk, $x, $maxW, self::BODY_SIZE_PT, self::LINE_HEIGHT_PT, 'Helvetica', '');
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

        $this->renderInlineTextBlock($pdf, $text, $x, $maxW, self::BODY_SIZE_PT, self::LINE_HEIGHT_PT, 'Helvetica', '');

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

        $marker = $this->pdfText($this->bulletGlyph($level));
        $this->ensureRoom($pdf, self::LINE_HEIGHT_PT);

        // marker
        // Ensure marker uses the base list font/style (avoid inheriting bold/italic from prior inline runs).
        $pdf->SetFont('Helvetica', '', self::BODY_SIZE_PT);
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
        // FPDF built-in fonts use WinAnsi (Windows-1252-ish). Only a small subset of Unicode is safe.
        // Keep nested markers ASCII to avoid mojibake in PDF output.
        if ($level === 1) return '•';
        if ($level === 2) return 'o';
        return '-';
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

        // Ensure marker uses the base list font/style (avoid inheriting bold/italic from prior inline runs).
        $pdf->SetFont('Helvetica', '', self::BODY_SIZE_PT);
        $pdf->SetX($markerX);
        $pdf->Cell(self::BULLET_COL_W_PT, self::LINE_HEIGHT_PT, $this->pdfText($marker), 0, 0, 'R');

        $this->renderListTextLines($pdf, $text, $textX, $maxW, true);
        $pdf->Ln(2.0);

        return ['textX' => $textX, 'maxW' => $maxW, 'counters' => $counters];
    }

    private function renderListTextLines(FPDF $pdf, string $text, float $textX, float $maxW, bool $firstLineNewRow): void
    {
        $runs = $this->parseInlineRunsSafe($text, 'Helvetica', '');
        $wrapped = $this->wrapInlineRuns($pdf, $runs, self::BODY_SIZE_PT, $maxW);
        foreach ($wrapped as $idx => $line) {
            if (!$line) continue;
            $this->ensureRoom($pdf, self::LINE_HEIGHT_PT);
            $pdf->SetX($textX);
            if ($idx === 0 && $firstLineNewRow) {
                $this->renderInlineLine($pdf, $line, $textX, $maxW, self::LINE_HEIGHT_PT, self::BODY_SIZE_PT);
            } else {
                $this->renderInlineLine($pdf, $line, $textX, $maxW, self::LINE_HEIGHT_PT, self::BODY_SIZE_PT);
            }
        }
        // Reset font so subsequent markers/blocks don't inherit the final inline run's style.
        $pdf->SetFont('Helvetica', '', self::BODY_SIZE_PT);
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
            $pdf->MultiCell($maxW, self::LINE_HEIGHT_PT, $this->pdfText(rtrim($ln, "\r\n")), 0, 'L', true);
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
        $text = $this->pdfText($text);
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

    /**
     * FPDF built-in fonts are not UTF-8. Convert to WinAnsi to avoid mojibake (e.g. "â€¢").
     * We prefer CP1252 with transliteration; fall back to the original string if conversion fails.
     */
    private function pdfText(string $s): string
    {
        if ($s === '') return '';
        $out = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
        if ($out === false) {
            $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
        }
        return ($out === false) ? $s : $out;
    }
}


