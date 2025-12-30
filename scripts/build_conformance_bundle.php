<?php

declare(strict_types=1);

/**
 * Build the DocDraw conformance bundle zip and write a .sha256 file next to it.
 *
 * Output:
 * - assets/downloads/docdraw-conformance-bundle-v1.zip
 * - assets/downloads/docdraw-conformance-bundle-v1.zip.sha256
 *
 * Bundle contents (relative to bundle root):
 * - README.md
 * - CONTRIBUTING.md
 * - examples/golden-manifest.json
 * - examples/source/**
 * - assets/examples/** (normalized goldens; PDFs may be absent)
 */

$root = dirname(__DIR__);
$outDir = $root . '/assets/downloads';
$bundleName = 'docdraw-conformance-bundle-v1.zip';
$zipPath = $outDir . '/' . $bundleName;
$shaPath = $zipPath . '.sha256';

function fail(string $msg, int $code = 1): never
{
    fwrite(STDERR, "ERROR: {$msg}\n");
    exit($code);
}

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if ($file->isDir()) {
            @rmdir($file->getPathname());
        } else {
            @unlink($file->getPathname());
        }
    }
    @rmdir($dir);
}

function mkdirp(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
        fail("Failed to create directory: {$dir}");
    }
}

function copy_file(string $src, string $dst): void
{
    mkdirp(dirname($dst));
    if (!copy($src, $dst)) {
        fail("Failed to copy {$src} -> {$dst}");
    }
}

function copy_tree(string $srcDir, string $dstDir): void
{
    if (!is_dir($srcDir)) {
        fail("Missing directory: {$srcDir}");
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        $rel = substr($file->getPathname(), strlen($srcDir) + 1);
        $dst = $dstDir . '/' . $rel;
        if ($file->isDir()) {
            mkdirp($dst);
        } else {
            copy_file($file->getPathname(), $dst);
        }
    }
}

if (!extension_loaded('zip')) {
    fail("PHP zip extension is required (ZipArchive).");
}

mkdirp($outDir);

$staging = sys_get_temp_dir() . '/docdraw-bundle-' . bin2hex(random_bytes(8));
mkdirp($staging);

try {
    // README inside bundle
    $readme = <<<MD
# DocDraw Conformance Bundle (v1)

This bundle contains the canonical conformance fixtures used to verify implementations of:

- DocDraw v1 (language)
- DMP-1 (Markdown profile)
- DD-PDF-1 (renderer profile; PDFs may be absent until the reference compiler exists)

## Contents
- \`examples/golden-manifest.json\` (source of truth)
- \`examples/source/\` (fixtures)
- \`assets/examples/\` (normalized goldens today; PDF goldens later)
- \`CONTRIBUTING.md\` (workflow and rules)

## Quick usage (repo layout)
If you unzip this into the DocDraw.org repo root:

```bash
make examples-update
make examples-check
```

MD;
    file_put_contents($staging . '/README.md', $readme . "\n");

    // Contributing
    if (!is_file($root . '/CONTRIBUTING.md')) {
        fail("Missing CONTRIBUTING.md");
    }
    copy_file($root . '/CONTRIBUTING.md', $staging . '/CONTRIBUTING.md');

    // Manifest + fixtures
    copy_file($root . '/examples/golden-manifest.json', $staging . '/examples/golden-manifest.json');
    copy_tree($root . '/examples/source', $staging . '/examples/source');

    // Goldens folder (normalized today; PDFs later)
    copy_tree($root . '/assets/examples', $staging . '/assets/examples');

    // Create zip
    if (is_file($zipPath)) {
        unlink($zipPath);
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        fail("Failed to create zip: {$zipPath}");
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($staging, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if (!$file->isFile()) continue;
        $abs = $file->getPathname();
        $rel = substr($abs, strlen($staging) + 1);
        $zip->addFile($abs, $rel);
    }
    $zip->close();

    // SHA256
    $bytes = file_get_contents($zipPath);
    if ($bytes === false) {
        fail("Failed to read built zip for hashing.");
    }
    $sha = hash('sha256', $bytes);
    $shaLine = $sha . "  " . $bundleName . "\n";
    file_put_contents($shaPath, $shaLine);

    echo "OK: wrote {$zipPath}\n";
    echo "OK: wrote {$shaPath}\n";
} finally {
    rrmdir($staging);
}


