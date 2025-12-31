<?php

declare(strict_types=1);

/**
 * Generate PDF goldens for PASS examples and update pdf_sha256 in the manifest.
 *
 * Phase 2 step (renderer harness wiring):
 * - Reads examples/golden-manifest.json
 * - For each PASS example:
 *   - renders PDF via DD-PDF-1 renderer into output.pdf_path
 *   - computes sha256 and updates output.pdf_sha256
 *   - sets output.compiler_version (stable string)
 *
 * NOTE:
 * - We intentionally do NOT update last_generated to avoid churn in git diffs.
 */

$root = dirname(__DIR__);
$manifestPath = $root . '/examples/golden-manifest.json';
$autoload = $root . '/vendor/autoload.php';

function fail(string $msg, int $code = 1): never
{
    fwrite(STDERR, "ERROR: {$msg}\n");
    exit($code);
}

if (!is_file($autoload)) {
    fail("Missing vendor/autoload.php. Run: composer install");
}
require_once $autoload;

require_once __DIR__ . '/docdraw_cli_lib.php';
require_once $root . '/src/pdf/DDPdf1Renderer.php';

if (!is_file($manifestPath)) {
    fail("Missing manifest: {$manifestPath}");
}
$json = file_get_contents($manifestPath);
if ($json === false) {
    fail("Failed to read manifest: {$manifestPath}");
}
$manifest = json_decode($json, true);
if (!is_array($manifest) || !isset($manifest['examples']) || !is_array($manifest['examples'])) {
    fail("Manifest invalid.");
}

$renderer = new \DocDraw\Pdf\DDPdf1Renderer();
$compilerVersion = 'docdraw-php-ddpdf1-proto';

$updated = 0;

foreach ($manifest['examples'] as &$ex) {
    if (!is_array($ex)) continue;
    $id = $ex['id'] ?? null;
    if (!is_string($id) || $id === '') continue;

    $expectedType = $ex['expected_result']['type'] ?? null;
    if ($expectedType !== 'pass') continue;

    $docdrawRel = $ex['source']['docdraw'] ?? null;
    if (!is_string($docdrawRel) || $docdrawRel === '') {
        fail("PASS example {$id} missing source.docdraw");
    }
    $docdrawAbs = $root . '/' . ltrim($docdrawRel, '/');
    if (!is_file($docdrawAbs)) {
        fail("Missing DocDraw source for {$id}: {$docdrawRel}");
    }
    $src = file_get_contents($docdrawAbs);
    if ($src === false) {
        fail("Failed to read DocDraw source for {$id}");
    }

    // Validate before rendering
    $val = docdraw_validate_v1($src);
    if (!$val['ok']) {
        $err = $val['error'] ?? ['code' => 'UNKNOWN', 'message' => 'Unknown error'];
        $line = isset($err['line']) ? ('line ' . $err['line'] . ': ') : '';
        fail("DocDraw validation failed for {$id}: {$line}{$err['code']}: {$err['message']}");
    }

    $pdfRel = $ex['output']['pdf_path'] ?? null;
    if (!is_string($pdfRel) || $pdfRel === '' || !str_starts_with($pdfRel, 'assets/examples/')) {
        fail("PASS example {$id} missing valid output.pdf_path");
    }
    $pdfAbs = $root . '/' . ltrim($pdfRel, '/');
    @mkdir(dirname($pdfAbs), 0770, true);

    $renderer->render($src, $pdfAbs);

    $bytes = file_get_contents($pdfAbs);
    if ($bytes === false) {
        fail("Failed to read generated PDF for {$id}");
    }
    $sha = hash('sha256', $bytes);

    $ex['output']['pdf_sha256'] = $sha;
    $ex['output']['compiler_version'] = $compilerVersion;
    $updated++;
}
unset($ex);

$encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if (file_put_contents($manifestPath, $encoded) === false) {
    fail("Failed to write updated manifest");
}

echo "OK: rendered PDFs + updated manifest for {$updated} example(s)\n";


