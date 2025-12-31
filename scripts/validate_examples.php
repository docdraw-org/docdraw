<?php

declare(strict_types=1);

/**
 * Phase 1 validator for DocDraw.org examples.
 *
 * Today:
 * - validates that examples/golden-manifest.json exists and is well-formed
 * - validates that referenced source files exist
 * - validates that output pdf_path (if present) is under assets/examples/
 * - prints a summary and exits non-zero on failure
 *
 * Later:
 * - will be extended to compute SHA256 for generated PDFs and update manifest
 */

$root = dirname(__DIR__);
$manifestPath = $root . '/examples/golden-manifest.json';

function fail(string $msg, int $code = 1): never
{
    fwrite(STDERR, "ERROR: {$msg}\n");
    exit($code);
}

if (!is_file($manifestPath)) {
    fail("Missing manifest: {$manifestPath}");
}

$json = file_get_contents($manifestPath);
if ($json === false) {
    fail("Failed to read manifest: {$manifestPath}");
}

$manifest = json_decode($json, true);
if (!is_array($manifest)) {
    fail("Manifest is not valid JSON object.");
}

if (!isset($manifest['manifest_version']) || !is_int($manifest['manifest_version'])) {
    fail("Manifest missing integer manifest_version.");
}

if (!isset($manifest['examples']) || !is_array($manifest['examples'])) {
    fail("Manifest missing examples array.");
}

$errors = 0;
$seenIds = [];

foreach ($manifest['examples'] as $i => $ex) {
    if (!is_array($ex)) {
        fwrite(STDERR, "ERROR: examples[$i] is not an object\n");
        $errors++;
        continue;
    }

    $id = $ex['id'] ?? null;
    if (!is_string($id) || $id === '') {
        fwrite(STDERR, "ERROR: examples[$i] missing non-empty id\n");
        $errors++;
        continue;
    }
    if (!preg_match('/^[a-z0-9-]+$/', $id)) {
        fwrite(STDERR, "ERROR: example id must match ^[a-z0-9-]+$: {$id}\n");
        $errors++;
    }
    if (isset($seenIds[$id])) {
        fwrite(STDERR, "ERROR: duplicate example id: {$id}\n");
        $errors++;
    }
    $seenIds[$id] = true;

    $expected = $ex['expected_result'] ?? null;
    $expectedType = is_array($expected) ? ($expected['type'] ?? null) : null;
    if ($expectedType !== 'pass' && $expectedType !== 'fail') {
        fwrite(STDERR, "ERROR: example {$id} expected_result.type must be 'pass' or 'fail'\n");
        $errors++;
    }

    $source = $ex['source'] ?? null;
    if (!is_array($source) || !$source) {
        fwrite(STDERR, "ERROR: example {$id} missing source\n");
        $errors++;
        continue;
    }

    foreach ($source as $key => $relPath) {
        if (!is_string($relPath) || $relPath === '') {
            fwrite(STDERR, "ERROR: example {$id} source.{$key} must be a string path\n");
            $errors++;
            continue;
        }
        $abs = $root . '/' . ltrim($relPath, '/');
        if (!is_file($abs)) {
            fwrite(STDERR, "ERROR: example {$id} missing file: {$relPath}\n");
            $errors++;
        }
    }

    $output = $ex['output'] ?? null;
    if ($output !== null) {
        if (!is_array($output)) {
            fwrite(STDERR, "ERROR: example {$id} output must be an object\n");
            $errors++;
        } else {
            $pdfPath = $output['pdf_path'] ?? null;
            if ($expectedType === 'pass') {
                if (!is_string($pdfPath) || $pdfPath === '') {
                    fwrite(STDERR, "ERROR: example {$id} (pass) must declare output.pdf_path\n");
                    $errors++;
                }
                $rp = $output['renderer_profile'] ?? null;
                if (!is_string($rp) || $rp === '') {
                    fwrite(STDERR, "ERROR: example {$id} (pass) must declare output.renderer_profile\n");
                    $errors++;
                }
                $normPath = $output['normalized_path'] ?? null;
                if (!is_string($normPath) || $normPath === '') {
                    fwrite(STDERR, "ERROR: example {$id} (pass) must declare output.normalized_path\n");
                    $errors++;
                }
                $normSha = $output['normalized_sha256'] ?? null;
                if (!is_string($normSha) || $normSha === '') {
                    fwrite(STDERR, "ERROR: example {$id} (pass) must declare output.normalized_sha256 (TBD allowed)\n");
                    $errors++;
                }
                $pdfSha = $output['pdf_sha256'] ?? null;
                if (!is_string($pdfSha) || $pdfSha === '' || $pdfSha === 'TBD') {
                    fwrite(STDERR, "ERROR: example {$id} (pass) must have output.pdf_sha256 populated (run: make examples-update)\n");
                    $errors++;
                }
                if (!isset($source['docdraw'])) {
                    fwrite(STDERR, "ERROR: example {$id} (pass) must include source.docdraw\n");
                    $errors++;
                }
            }

            if ($pdfPath !== null) {
                if (!is_string($pdfPath) || $pdfPath === '') {
                    fwrite(STDERR, "ERROR: example {$id} output.pdf_path must be string or null\n");
                    $errors++;
                } elseif (!str_starts_with($pdfPath, 'assets/examples/')) {
                    fwrite(STDERR, "ERROR: example {$id} output.pdf_path must be under assets/examples/: {$pdfPath}\n");
                    $errors++;
                } elseif ($expectedType === 'pass') {
                    $absPdf = $root . '/' . ltrim($pdfPath, '/');
                    if (!is_file($absPdf)) {
                        fwrite(STDERR, "ERROR: example {$id} expected PDF missing: {$pdfPath}\n");
                        $errors++;
                    } else {
                        $bytes = file_get_contents($absPdf);
                        if ($bytes === false) {
                            fwrite(STDERR, "ERROR: example {$id} cannot read PDF: {$pdfPath}\n");
                            $errors++;
                        } else {
                            $sha = hash('sha256', $bytes);
                            $pdfSha = $output['pdf_sha256'] ?? '';
                            if (is_string($pdfSha) && $pdfSha !== '' && $pdfSha !== 'TBD' && $pdfSha !== $sha) {
                                fwrite(STDERR, "ERROR: example {$id} pdf_sha256 mismatch (manifest vs file)\n");
                                $errors++;
                            }
                        }
                    }
                }
            }

            $normPath = $output['normalized_path'] ?? null;
            if ($normPath !== null) {
                if (!is_string($normPath) || $normPath === '') {
                    fwrite(STDERR, "ERROR: example {$id} output.normalized_path must be string or null\n");
                    $errors++;
                } elseif (!str_starts_with($normPath, 'assets/examples/')) {
                    fwrite(STDERR, "ERROR: example {$id} output.normalized_path must be under assets/examples/: {$normPath}\n");
                    $errors++;
                }
            }
        }
    }

    if ($expectedType === 'fail') {
        $codes = $expected['expected_error_codes'] ?? null;
        if (!is_array($codes) || count($codes) === 0) {
            fwrite(STDERR, "ERROR: example {$id} (fail) must declare expected_result.expected_error_codes[]\n");
            $errors++;
        }
    }

    $contract = $ex['expected_output_contract'] ?? null;
    if (!is_array($contract) || count($contract) === 0) {
        fwrite(STDERR, "ERROR: example {$id} expected_output_contract must be a non-empty array\n");
        $errors++;
    }
}

if ($errors > 0) {
    fail("Validation failed with {$errors} error(s).", 2);
}

echo "OK: examples manifest validated (" . count($manifest['examples']) . " examples)\n";


