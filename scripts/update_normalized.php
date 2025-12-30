<?php

declare(strict_types=1);

/**
 * Generate deterministic "text-only goldens" (normalized DocDraw) for PASS examples.
 *
 * - Reads examples/golden-manifest.json
 * - For each PASS example with a DocDraw source:
 *   - writes assets/examples/<id>.normalized.docdraw
 *   - computes sha256 and updates manifest output.normalized_sha256
 *
 * This is Phase 1 (no PDF compiler required).
 */

$root = dirname(__DIR__);
$manifestPath = $root . '/examples/golden-manifest.json';

function fail(string $msg, int $code = 1): never
{
    fwrite(STDERR, "ERROR: {$msg}\n");
    exit($code);
}

function normalize_docdraw(string $s): string
{
    // Normalize line endings to LF
    $s = str_replace(["\r\n", "\r"], "\n", $s);

    $lines = explode("\n", $s);
    $out = [];
    $blankRun = 0;

    foreach ($lines as $line) {
        // Trim trailing whitespace; preserve leading whitespace (though canonical DocDraw shouldn't use it).
        $line = rtrim($line, " \t");

        if ($line === '') {
            $blankRun++;
            // Collapse multiple blank lines to a single blank line
            if ($blankRun > 1) {
                continue;
            }
            $out[] = '';
            continue;
        }

        $blankRun = 0;
        $out[] = $line;
    }

    // Remove leading blank lines
    while ($out && $out[0] === '') {
        array_shift($out);
    }

    // Remove trailing blank lines
    while ($out && end($out) === '') {
        array_pop($out);
    }

    return implode("\n", $out) . "\n";
}

if (!is_file($manifestPath)) {
    fail("Missing manifest: {$manifestPath}");
}
$json = file_get_contents($manifestPath);
if ($json === false) {
    fail("Failed to read manifest: {$manifestPath}");
}
$manifest = json_decode($json, true);
if (!is_array($manifest) || !isset($manifest['examples']) || !is_array($manifest['examples'])) {
    fail("Manifest is invalid.");
}

$updated = 0;

foreach ($manifest['examples'] as &$ex) {
    if (!is_array($ex)) {
        continue;
    }

    $id = $ex['id'] ?? null;
    if (!is_string($id) || $id === '') {
        continue;
    }

    $expected = $ex['expected_result']['type'] ?? null;
    if ($expected !== 'pass') {
        continue;
    }

    $docdrawRel = $ex['source']['docdraw'] ?? null;
    if (!is_string($docdrawRel) || $docdrawRel === '') {
        continue;
    }
    $docdrawAbs = $root . '/' . ltrim($docdrawRel, '/');
    if (!is_file($docdrawAbs)) {
        fail("Missing DocDraw source for {$id}: {$docdrawRel}");
    }

    $normalizedRel = $ex['output']['normalized_path'] ?? null;
    if (!is_string($normalizedRel) || $normalizedRel === '' || !str_starts_with($normalizedRel, 'assets/examples/')) {
        fail("Example {$id} missing valid output.normalized_path");
    }
    $normalizedAbs = $root . '/' . ltrim($normalizedRel, '/');

    $src = file_get_contents($docdrawAbs);
    if ($src === false) {
        fail("Failed to read DocDraw source for {$id}");
    }

    $normalized = normalize_docdraw($src);
    if (!is_dir(dirname($normalizedAbs))) {
        fail("Missing output directory: " . dirname($normalizedAbs));
    }
    if (file_put_contents($normalizedAbs, $normalized) === false) {
        fail("Failed to write normalized output for {$id}");
    }

    $sha = hash('sha256', $normalized);
    $ex['output']['normalized_sha256'] = $sha;
    $updated++;
}
unset($ex);

$encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if (file_put_contents($manifestPath, $encoded) === false) {
    fail("Failed to write updated manifest");
}

echo "OK: wrote normalized outputs + updated manifest for {$updated} example(s)\n";


