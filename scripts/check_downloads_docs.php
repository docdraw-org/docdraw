<?php

declare(strict_types=1);

/**
 * Check that docs/downloads.md matches the current bundle sha256s.
 */

$root = dirname(__DIR__);
$conformanceRel = 'assets/downloads/docdraw-conformance-bundle-v1.zip';
$specRel = 'assets/downloads/docdraw-spec-bundle-v1.0.zip';
$conformanceShaRel = $conformanceRel . '.sha256';
$specShaRel = $specRel . '.sha256';
$conformanceShaPath = $root . '/' . $conformanceShaRel;
$specShaPath = $root . '/' . $specShaRel;
$docsPath = $root . '/docs/downloads.md';

function fail(string $msg, int $code = 1): never
{
    fwrite(STDERR, "ERROR: {$msg}\n");
    exit($code);
}

function read_sha(string $path): string
{
    $shaLine = trim((string)file_get_contents($path));
    if ($shaLine === '') {
        fail("Empty sha file: {$path}");
    }
    $parts = preg_split('/\s+/', $shaLine);
    $sha = $parts[0] ?? '';
    if (!preg_match('/^[a-f0-9]{64}$/', $sha)) {
        fail("Invalid sha format in {$path}");
    }
    return $sha;
}

if (!is_file($conformanceShaPath)) {
    fail("Missing sha file: {$conformanceShaPath}. Run: make bundle");
}
if (!is_file($specShaPath)) {
    fail("Missing sha file: {$specShaPath}. Run: make bundle");
}

$conformanceSha = read_sha($conformanceShaPath);
$specSha = read_sha($specShaPath);

if (!is_file($docsPath)) {
    fail("Missing docs file: {$docsPath}. Run: make bundle");
}
$docs = (string)file_get_contents($docsPath);

if (!str_contains($docs, "## Conformance Bundle v1")) {
    fail("downloads.md does not look like the generated file.");
}

if (!str_contains($docs, "- **Bundle**: `/{$conformanceRel}`") || !str_contains($docs, "- **SHA256**: `{$conformanceSha}`")) {
    fwrite(STDERR, "ERROR: downloads.md conformance bundle SHA does not match.\n");
    fwrite(STDERR, "Run: make bundle\n");
    exit(3);
}
if (!str_contains($docs, "- **Bundle**: `/{$specRel}`") || !str_contains($docs, "- **SHA256**: `{$specSha}`")) {
    fwrite(STDERR, "ERROR: downloads.md spec bundle SHA does not match.\n");
    fwrite(STDERR, "Run: make bundle\n");
    exit(3);
}

echo "OK: docs/downloads.md SHAs match bundles\n";


