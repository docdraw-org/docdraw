<?php

declare(strict_types=1);

/**
 * Check that DD-PDF-1 rendering is deterministic.
 *
 * For each PASS example in examples/golden-manifest.json:
 * - render the DocDraw source twice to two temp files
 * - sha256 must match between the two renders
 * - sha256 must match the manifest's output.pdf_sha256
 *
 * This is intended for CI / pre-commit to catch non-determinism (timestamps, object ids, etc).
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
$checked = 0;

/** @var array<int, mixed> $examples */
$examples = $manifest['examples'];
foreach ($examples as $ex) {
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

    $val = docdraw_validate_v1($src);
    if (!$val['ok']) {
        $err = $val['error'] ?? ['code' => 'UNKNOWN', 'message' => 'Unknown error'];
        $line = isset($err['line']) ? ('line ' . $err['line'] . ': ') : '';
        fail("DocDraw validation failed for {$id}: {$line}{$err['code']}: {$err['message']}");
    }

    $expectedSha = $ex['output']['pdf_sha256'] ?? null;
    if (!is_string($expectedSha) || !preg_match('/^[a-f0-9]{64}$/', $expectedSha)) {
        fail("PASS example {$id} missing valid output.pdf_sha256 (run: make examples-update)");
    }

    $opts = [];
    if (isset($ex['output']['renderer_options']) && is_array($ex['output']['renderer_options'])) {
        $opts = $ex['output']['renderer_options'];
    }

    $tmpDir = sys_get_temp_dir();
    $tmp1 = $tmpDir . '/docdraw-ddpdf1-' . $id . '-1-' . getmypid() . '.pdf';
    $tmp2 = $tmpDir . '/docdraw-ddpdf1-' . $id . '-2-' . getmypid() . '.pdf';

    // Ensure no stale temp files.
    @unlink($tmp1);
    @unlink($tmp2);

    $renderer->render($src, $tmp1, $opts);
    $renderer->render($src, $tmp2, $opts);

    $bytes1 = file_get_contents($tmp1);
    $bytes2 = file_get_contents($tmp2);
    @unlink($tmp1);
    @unlink($tmp2);

    if ($bytes1 === false || $bytes2 === false) {
        fail("Failed to read temp PDFs for {$id}");
    }

    $sha1 = hash('sha256', $bytes1);
    $sha2 = hash('sha256', $bytes2);

    if ($sha1 !== $sha2) {
        fail("Non-deterministic PDF output for {$id}: sha1={$sha1} sha2={$sha2}", 3);
    }
    if ($sha1 !== $expectedSha) {
        fail("PDF sha256 drift for {$id}: manifest={$expectedSha} rendered={$sha1} (run: make examples-update)", 4);
    }

    $checked++;
}

echo "OK: DD-PDF-1 deterministic for {$checked} PASS example(s)\n";


