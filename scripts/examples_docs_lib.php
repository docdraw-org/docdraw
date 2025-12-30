<?php

declare(strict_types=1);

/**
 * Shared logic for generating docs/examples.md from examples/golden-manifest.json.
 */

/**
 * @param array<string,mixed> $manifest
 */
function docdraw_generate_examples_md(array $manifest): string
{
    /** @var array<int, mixed> $examples */
    $examples = $manifest['examples'] ?? [];

    $lines = [];
    $lines[] = "# Golden examples";
    $lines[] = "";
    $lines[] = "> This page is generated from `examples/golden-manifest.json`. Do not edit by hand.";
    $lines[] = "";
    $lines[] = "Golden examples are the “trust builder” for a standard: **input + deterministic compiler + expected output** that implementations can compare against.";
    $lines[] = "";
    $lines[] = "## Phase 1 (now): useful placeholders (no dead links)";
    $lines[] = "Until the reference compiler exists, we keep **stable target filenames** for golden PDFs, but we do **not** hand-upload “random” PDFs:";
    $lines[] = "";
    $lines[] = "- Golden PDFs must be produced by the **reference compiler** to be meaningful.";
    $lines[] = "- Instead, each example defines an **Expected Output Contract** that implementers can use immediately.";
    $lines[] = "- We also publish a deterministic **text-only golden** (`*.normalized.docdraw`) + SHA256 today.";
    $lines[] = "";
    $lines[] = "Source of truth:";
    $lines[] = "- `examples/golden-manifest.json`";
    $lines[] = "- `examples/source/` (inputs)";
    $lines[] = "- `assets/examples/` (future compiler-produced PDFs + normalized outputs)";
    $lines[] = "";
    $lines[] = "## Examples";
    $lines[] = "";

    foreach ($examples as $ex) {
        if (!is_array($ex)) {
            continue;
        }
        $id = $ex['id'] ?? '';
        $title = $ex['title'] ?? $id;
        if (!is_string($id) || $id === '') {
            continue;
        }
        if (!is_string($title) || $title === '') {
            $title = $id;
        }

        $lines[] = "### " . $title;
        $lines[] = "";
        $lines[] = "- **id**: `" . $id . "`";

        $expectedType = $ex['expected_result']['type'] ?? null;
        if ($expectedType === 'fail') {
            $codes = $ex['expected_result']['expected_error_codes'] ?? [];
            $codesStr = is_array($codes) ? implode(', ', array_map(fn($c) => (string)$c, $codes)) : 'TBD';
            $lines[] = "- **expected**: FAIL (`" . ($ex['expected_result']['stage'] ?? 'TBD') . "`) codes: `" . ($codesStr ?: 'TBD') . "`";
        } else {
            $lines[] = "- **expected**: PASS";
        }

        $source = $ex['source'] ?? [];
        if (is_array($source)) {
            if (isset($source['docdraw'])) {
                $lines[] = "- **DocDraw input**: `" . $source['docdraw'] . "`";
            }
            if (isset($source['dmp1_markdown'])) {
                $lines[] = "- **DMP-1 input**: `" . $source['dmp1_markdown'] . "`";
            }
        }

        $out = $ex['output'] ?? [];
        if (is_array($out)) {
            $pdfPath = $out['pdf_path'] ?? null;
            if (is_string($pdfPath) && $pdfPath !== '') {
                $lines[] = "- **Golden PDF target**: `" . $pdfPath . "`";
            }
            $rendererProfile = $out['renderer_profile'] ?? null;
            if (is_string($rendererProfile) && $rendererProfile !== '') {
                $lines[] = "- **renderer_profile**: `" . $rendererProfile . "`";
            }
            $normPath = $out['normalized_path'] ?? null;
            if (is_string($normPath) && $normPath !== '') {
                $lines[] = "- **Normalized golden (today)**: `" . $normPath . "`";
                $lines[] = "- **normalized_sha256**: `" . ($out['normalized_sha256'] ?? 'TBD') . "`";
            }
            $lines[] = "- **compiler_version**: `" . ($out['compiler_version'] ?? 'TBD') . "`";
            $lines[] = "- **pdf_sha256**: `" . ($out['pdf_sha256'] ?? 'TBD') . "`";
            $lines[] = "- **last_generated**: `" . ($out['last_generated'] ?? 'TBD') . "`";
        }

        $contract = $ex['expected_output_contract'] ?? [];
        $lines[] = "- **Expected Output Contract**:";
        if (is_array($contract) && $contract) {
            foreach ($contract as $c) {
                $lines[] = "  - " . (string)$c;
            }
        } else {
            $lines[] = "  - TBD";
        }
        $lines[] = "";
    }

    $lines[] = "## Phase 2 (later): automated golden PDFs";
    $lines[] = "As soon as a reference compiler can render PDFs:";
    $lines[] = "- Generate PDFs into `assets/examples/`";
    $lines[] = "- Update `examples/golden-manifest.json` with `compiler_version`, `pdf_sha256`, and `last_generated`";
    $lines[] = "- Do not edit PDFs by hand";
    $lines[] = "";

    return implode("\n", $lines) . "\n";
}


