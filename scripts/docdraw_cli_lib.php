<?php

declare(strict_types=1);

/**
 * Minimal DocDraw tooling (Phase 1).
 *
 * Provides:
 * - normalization for DocDraw text
 * - basic DocDraw v1 validation (structural checks useful today)
 * - basic DMP-1 Markdown -> DocDraw conversion (subset)
 *
 * Note: This is intentionally minimal and will evolve into a proper reference compiler/tooling.
 */

/**
 * @return array{ok:bool, error?:array{code:string, message:string, line?:int}}
 */
function docdraw_validate_v1(string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = explode("\n", $text);

    $inPBlock = false;
    $inCodeBlock = false;

    $prevListLevel = null;
    $prevWasListItem = false;

    foreach ($lines as $idx0 => $lineRaw) {
        $lineNo = $idx0 + 1;
        $line = rtrim($lineRaw, " \t");
        $trim = trim($line);

        if ($trim === '') {
            // Blank line breaks list adjacency semantics.
            $prevWasListItem = false;
            $prevListLevel = null;
            continue;
        }

        if ($inCodeBlock) {
            if ($trim === '}') {
                $inCodeBlock = false;
            }
            continue;
        }

        if ($inPBlock) {
            if ($trim === '}') {
                $inPBlock = false;
                continue;
            }
            if ($trim === 'br') {
                continue;
            }
            // content lines allowed
            continue;
        }

        // Block openers
        if ($trim === 'p{') {
            $inPBlock = true;
            $prevWasListItem = false;
            $prevListLevel = null;
            continue;
        }
        if ($trim === 'code{') {
            $inCodeBlock = true;
            $prevWasListItem = false;
            $prevListLevel = null;
            continue;
        }

        // br only allowed inside p{}
        if ($trim === 'br') {
            return [
                'ok' => false,
                'error' => [
                    'code' => 'DDV1_BR_OUTSIDE_PBLOCK',
                    'message' => '`br` is only allowed inside p{ }.',
                    'line' => $lineNo,
                ],
            ];
        }

        // Continuation lines must follow a list item
        if (preg_match('/^\.\.:\s+/', $trim)) {
            if (!$prevWasListItem) {
                return [
                    'ok' => false,
                    'error' => [
                        'code' => 'DDV1_CONTINUATION_WITHOUT_LIST_ITEM',
                        'message' => 'Continuation line must follow a list item.',
                        'line' => $lineNo,
                    ],
                ];
            }
            continue;
        }

        // Divider
        if ($trim === '---') {
            $prevWasListItem = false;
            $prevListLevel = null;
            continue;
        }

        // Headings
        if (preg_match('/^#([1-6]):\s+.+$/', $trim)) {
            $prevWasListItem = false;
            $prevListLevel = null;
            continue;
        }

        // Paragraph single-line
        if (preg_match('/^p:\s+.*$/', $trim)) {
            $prevWasListItem = false;
            $prevListLevel = null;
            continue;
        }

        // Quote single-line
        if (preg_match('/^q:\s+.*$/', $trim)) {
            $prevWasListItem = false;
            $prevListLevel = null;
            continue;
        }

        // List items (bullet and ordered)
        if (preg_match('/^-([1-9]):\s+.+$/', $trim, $m)) {
            $lvl = (int)$m[1];
            if ($prevWasListItem && $prevListLevel !== null && ($lvl - $prevListLevel) > 1) {
                return [
                    'ok' => false,
                    'error' => [
                        'code' => 'DDV1_LIST_LEVEL_JUMP',
                        'message' => "List level jumped from {$prevListLevel} to {$lvl}; levels may only increase by 1 between adjacent items.",
                        'line' => $lineNo,
                    ],
                ];
            }
            $prevWasListItem = true;
            $prevListLevel = $lvl;
            continue;
        }
        if (preg_match('/^1-([1-9]):\s+.+$/', $trim, $m)) {
            $lvl = (int)$m[1];
            if ($prevWasListItem && $prevListLevel !== null && ($lvl - $prevListLevel) > 1) {
                return [
                    'ok' => false,
                    'error' => [
                        'code' => 'DDV1_LIST_LEVEL_JUMP',
                        'message' => "List level jumped from {$prevListLevel} to {$lvl}; levels may only increase by 1 between adjacent items.",
                        'line' => $lineNo,
                    ],
                ];
            }
            $prevWasListItem = true;
            $prevListLevel = $lvl;
            continue;
        }

        // Any other non-empty line is unknown in DocDraw v1.
        return [
            'ok' => false,
            'error' => [
                'code' => 'DDV1_UNKNOWN_LINE',
                'message' => 'Unrecognized line in DocDraw v1.',
                'line' => $lineNo,
            ],
        ];
    }

    if ($inPBlock) {
        return ['ok' => false, 'error' => ['code' => 'DDV1_UNCLOSED_PBLOCK', 'message' => 'Unclosed p{ } block.']];
    }
    if ($inCodeBlock) {
        return ['ok' => false, 'error' => ['code' => 'DDV1_UNCLOSED_CODEBLOCK', 'message' => 'Unclosed code{ } block.']];
    }

    return ['ok' => true];
}

function docdraw_normalize(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = explode("\n", $text);

    $out = [];
    $blankRun = 0;
    foreach ($lines as $line) {
        $line = rtrim($line, " \t");
        if ($line === '') {
            $blankRun++;
            if ($blankRun > 1) {
                continue;
            }
            $out[] = '';
            continue;
        }
        $blankRun = 0;
        $out[] = $line;
    }

    while ($out && $out[0] === '') {
        array_shift($out);
    }
    while ($out && end($out) === '') {
        array_pop($out);
    }

    return implode("\n", $out) . "\n";
}

/**
 * Minimal DMP-1 Markdown -> DocDraw conversion.
 *
 * @return array{ok:bool, docdraw?:string, error?:array{code:string, message:string, line?:int}}
 */
function dmp1_convert_to_docdraw(string $markdown): array
{
    $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
    $lines = explode("\n", $markdown);

    $out = [];
    $para = [];

    $flushPara = function () use (&$out, &$para) {
        if (!$para) return;
        $text = trim(implode(' ', array_map('trim', $para)));
        if ($text !== '') {
            $out[] = 'p: ' . $text;
        }
        $para = [];
    };

    foreach ($lines as $i0 => $lineRaw) {
        $lineNo = $i0 + 1;
        if (str_contains($lineRaw, "\t")) {
            return ['ok' => false, 'error' => ['code' => 'DMP1_TABS_INVALID', 'message' => 'Tabs are not allowed in DMP-1.', 'line' => $lineNo]];
        }
        $line = rtrim($lineRaw, " \t");
        $trim = trim($line);

        if ($trim === '') {
            $flushPara();
            continue;
        }

        // Reject some obvious unsupported constructs
        if (preg_match('/^<[^>]+>/', $trim)) {
            return ['ok' => false, 'error' => ['code' => 'DMP1_HTML_UNSUPPORTED', 'message' => 'HTML is not supported in DMP-1.', 'line' => $lineNo]];
        }
        if (preg_match('/^\|.+\|$/', $trim)) {
            return ['ok' => false, 'error' => ['code' => 'DMP1_TABLES_UNSUPPORTED', 'message' => 'Tables are not supported in DMP-1.', 'line' => $lineNo]];
        }
        if (preg_match('/^- \[[ xX]\]\s+/', $trim)) {
            return ['ok' => false, 'error' => ['code' => 'DMP1_TASK_LIST_UNSUPPORTED', 'message' => 'Task lists are not supported in DMP-1.', 'line' => $lineNo]];
        }

        // Headings (# only)
        if (preg_match('/^(#{1,6})\s+(.+)$/', $trim, $m)) {
            $flushPara();
            $lvl = strlen($m[1]);
            $text = preg_replace('/\s+#+\s*$/', '', $m[2]) ?? $m[2];
            $text = trim($text);
            $out[] = '#' . $lvl . ': ' . $text;
            continue;
        }

        // Lists (4 spaces per level)
        if (preg_match('/^(\s*)([-*])\s+(.+)$/', $line, $m)) {
            $flushPara();
            $indent = strlen($m[1]);
            if ($indent % 4 !== 0) {
                return ['ok' => false, 'error' => ['code' => 'AMBIGUOUS_LIST_INDENT', 'message' => 'List indentation must increase by exactly 4 spaces per level.', 'line' => $lineNo]];
            }
            $level = intdiv($indent, 4) + 1;
            $out[] = '-' . $level . ': ' . trim($m[3]);
            continue;
        }
        if (preg_match('/^(\s*)(\d+)\.\s+(.+)$/', $line, $m)) {
            $flushPara();
            $indent = strlen($m[1]);
            if ($indent % 4 !== 0) {
                return ['ok' => false, 'error' => ['code' => 'AMBIGUOUS_LIST_INDENT', 'message' => 'List indentation must increase by exactly 4 spaces per level.', 'line' => $lineNo]];
            }
            $level = intdiv($indent, 4) + 1;
            $out[] = '1-' . $level . ': ' . trim($m[3]);
            continue;
        }

        // Blockquotes (single line)
        if (preg_match('/^>\s?(.*)$/', $trim, $m)) {
            $flushPara();
            $out[] = 'q: ' . trim($m[1]);
            continue;
        }

        // Fenced code blocks unsupported in conversion in this minimal shim (for now)
        if (preg_match('/^```/', $trim)) {
            return ['ok' => false, 'error' => ['code' => 'DMP1_CODEBLOCK_UNSUPPORTED_IN_SHIM', 'message' => 'Fenced code blocks are not yet supported by the CLI shim converter.', 'line' => $lineNo]];
        }

        // Otherwise paragraph text
        $para[] = $trim;
    }

    $flushPara();
    $docdraw = implode("\n", $out) . "\n";
    return ['ok' => true, 'docdraw' => $docdraw];
}


