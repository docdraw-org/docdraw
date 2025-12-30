<?php

declare(strict_types=1);

/**
 * Generate docs/downloads.md from the bundle sha256 files.
 */

$root = dirname(__DIR__);
$conformanceRel = 'assets/downloads/docdraw-conformance-bundle-v1.zip';
$specRel = 'assets/downloads/docdraw-spec-bundle-v1.0.zip';

$conformanceShaRel = $conformanceRel . '.sha256';
$specShaRel = $specRel . '.sha256';

$conformanceShaPath = $root . '/' . $conformanceShaRel;
$specShaPath = $root . '/' . $specShaRel;
$outPath = $root . '/docs/downloads.md';

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

$md = [];
$md[] = "# Downloads";
$md[] = "";
$md[] = "## Conformance Bundle v1";
$md[] = "The conformance bundle is the canonical fixture set used to verify conformance to **DocDraw v1** and related profiles (like **DD-PDF-1** once PDFs exist).";
$md[] = "";
$md[] = "- **Bundle**: `/$conformanceRel`";
$md[] = "- **SHA256**: `$conformanceSha`";
$md[] = "- **SHA file**: `/$conformanceShaRel`";
$md[] = "";
$md[] = "### What’s inside";
$md[] = "- `examples/golden-manifest.json`";
$md[] = "- `examples/source/**`";
$md[] = "- `assets/examples/**` (includes normalized goldens today; PDFs may be absent for now)";
$md[] = "- `CONTRIBUTING.md`";
$md[] = "- `README.md` (bundle usage)";
$md[] = "";
$md[] = "### Verify integrity";
$md[] = "Download the bundle and verify the checksum:";
$md[] = "";
$md[] = "```text";
$md[] = "sha256sum -c docdraw-conformance-bundle-v1.zip.sha256";
$md[] = "```";
$md[] = "";
$md[] = "## Spec Release Bundle v1.0";
$md[] = "A snapshot release artifact for the **DocDraw v1.0** standard (docs + examples + goldens).";
$md[] = "";
$md[] = "- **Bundle**: `/$specRel`";
$md[] = "- **SHA256**: `$specSha`";
$md[] = "- **SHA file**: `/$specShaRel`";
$md[] = "";
$md[] = "### Verify integrity";
$md[] = "```text";
$md[] = "sha256sum -c docdraw-spec-bundle-v1.0.zip.sha256";
$md[] = "```";
$md[] = "";
$md[] = "### Minimal usage (in repo)";
$md[] = "```text";
$md[] = "make examples-update";
$md[] = "make examples-check";
$md[] = "```";
$md[] = "";

$content = implode("\n", $md) . "\n";
if (file_put_contents($outPath, $content) === false) {
    fail("Failed to write: {$outPath}");
}

echo "OK: generated docs/downloads.md\n";


