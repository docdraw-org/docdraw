# Quickstart (5 minutes): validate + normalize + goldens

This quickstart is designed to **work end-to-end today**: validate, normalize, and render deterministic PDFs.

What you’ll do:
1) Write a DocDraw file
2) Validate it (fail fast)
3) Generate deterministic “text-only golden” output (normalized DocDraw) + SHA256
4) Render a deterministic PDF (DD‑PDF‑1) + SHA256

## 1) Write a DocDraw file
Create a file (example):

```text
#1: Example Document
p: This is a paragraph.

-1: First bullet
-2: Nested bullet
-1: Another top-level bullet
..: with a continuation line
```

## 2) Validate it
Use the CLI shim:

```text
./bin/docdraw validate path/to/file.docdraw
```

If invalid, you’ll get a stable error code + line number.

Installation: [Installation](installation.md)

## 3) Normalize it (deterministic)
Normalization is a deterministic, text-only “golden” artifact:

```text
./bin/docdraw normalize path/to/file.docdraw -o path/to/file.normalized.docdraw
sha256sum path/to/file.normalized.docdraw
```

## 4) (Optional) Use the examples harness
The examples system is fully automated:

```text
make examples-update
make examples-check
```

That will:
- generate `assets/examples/*.normalized.docdraw`
- render `assets/examples/*.pdf`
- compute/store SHA256s in `examples/golden-manifest.json`
- regenerate `docs/examples.md` from the manifest (no drift)

## 5) Render a deterministic PDF (DD‑PDF‑1)

```text
./bin/docdraw render path/to/file.docdraw -o path/to/file.pdf
sha256sum path/to/file.pdf
```


