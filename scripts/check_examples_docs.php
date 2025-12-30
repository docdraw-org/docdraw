<?php

declare(strict_types=1);

/**
 * Check that docs/examples.md is up-to-date with examples/golden-manifest.json.
 *
 * This is intended for CI / pre-commit:
 * - exits 0 if docs/examples.md matches the generated content
 * - exits non-zero if it does not
 */

$root = dirname(__DIR__);
$manifestPath = $root . '/examples/golden-manifest.json';
$docsPath = $root . '/docs/examples.md';

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

$expected = docdraw_generate_examples_md($manifest);

if (!is_file($docsPath)) {
    fail("Missing docs file: {$docsPath}", 2);
}
$actual = file_get_contents($docsPath);
if ($actual === false) {
    fail("Failed to read docs file: {$docsPath}", 2);
}

// Normalize line endings for comparison.
$expected = str_replace(["\r\n", "\r"], "\n", $expected);
$actual = str_replace(["\r\n", "\r"], "\n", $actual);

if ($expected !== $actual) {
    fwrite(STDERR, "ERROR: docs/examples.md is out of date.\n");
    fwrite(STDERR, "Run: make examples-update\n");
    exit(3);
}

echo "OK: docs/examples.md matches examples/golden-manifest.json\n";


