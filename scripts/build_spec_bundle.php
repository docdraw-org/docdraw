<?php

declare(strict_types=1);

/**
 * Build a DocDraw v1.0 "spec release bundle" zip and write a .sha256 file next to it.
 *
 * Output:
 * - assets/downloads/docdraw-spec-bundle-v1.0.zip
 * - assets/downloads/docdraw-spec-bundle-v1.0.zip.sha256
 *
 * Bundle contents (relative to bundle root):
 * - README.md
 * - docs/** (markdown spec pages + nav.json)
 * - examples/golden-manifest.json
 * - examples/source/**
 * - assets/examples/** (normalized goldens; PDFs may be absent)
 * - CONTRIBUTING.md
 */

$root = dirname(__DIR__);
$outDir = $root . '/assets/downloads';
$bundleName = 'docdraw-spec-bundle-v1.0.zip';
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

$staging = sys_get_temp_dir() . '/docdraw-spec-bundle-' . bin2hex(random_bytes(8));
mkdirp($staging);

try {
    $readme = <<<MD
# DocDraw Spec Release Bundle (v1.0)

This bundle is a snapshot of the DocDraw v1.0 standard and its executable conformance materials.

## Contents
- `docs/` (spec pages + navigation)
- `examples/golden-manifest.json` (examples source of truth)
- `examples/source/` (fixtures)
- `assets/examples/` (normalized goldens today; PDF goldens later)
- `CONTRIBUTING.md`

## Verification
Use the accompanying `.sha256` file to verify integrity.

MD;
    file_put_contents($staging . '/README.md', $readme . "\n");

    if (!is_file($root . '/CONTRIBUTING.md')) {
        fail("Missing CONTRIBUTING.md");
    }
    copy_file($root . '/CONTRIBUTING.md', $staging . '/CONTRIBUTING.md');

    // Docs snapshot
    copy_tree($root . '/docs', $staging . '/docs');

    // Manifest + fixtures
    copy_file($root . '/examples/golden-manifest.json', $staging . '/examples/golden-manifest.json');
    copy_tree($root . '/examples/source', $staging . '/examples/source');

    // Goldens
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

    $bytes = file_get_contents($zipPath);
    if ($bytes === false) {
        fail("Failed to read built zip for hashing.");
    }
    $sha = hash('sha256', $bytes);
    file_put_contents($shaPath, $sha . "  " . $bundleName . "\n");

    echo "OK: wrote {$zipPath}\n";
    echo "OK: wrote {$shaPath}\n";
} finally {
    rrmdir($staging);
}


