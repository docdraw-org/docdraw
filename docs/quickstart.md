# Quickstart (5 minutes): validate + normalize + goldens

This quickstart is designed to **work end-to-end today**, even before PDFs exist.

What you’ll do:
1) Write a DocDraw file
2) Validate it (fail fast)
3) Generate deterministic “text-only golden” output (normalized DocDraw) + SHA256

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
- compute/store SHA256s in `examples/golden-manifest.json`
- regenerate `docs/examples.md` from the manifest (no drift)

## Next: PDFs (later)
When the reference compiler exists, we’ll add PDF generation into the same workflow and store `pdf_sha256` in the manifest.


