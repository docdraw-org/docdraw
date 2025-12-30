<?php

declare(strict_types=1);

/**
 * Generate docs/examples.md from examples/golden-manifest.json (source of truth).
 *
 * This prevents drift between the manifest and documentation.
 */

$root = dirname(__DIR__);
$manifestPath = $root . '/examples/golden-manifest.json';
$outPath = $root . '/docs/examples.md';

require_once __DIR__ . '/examples_docs_lib.php';

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
if (!is_array($manifest) || !isset($manifest['examples']) || !is_array($manifest['examples'])) {
    fail("Manifest invalid.");
}

$content = docdraw_generate_examples_md($manifest);
if (file_put_contents($outPath, $content) === false) {
    fail("Failed to write: {$outPath}");
}

echo "OK: generated docs/examples.md from manifest (" . count($manifest['examples']) . " examples)\n";


