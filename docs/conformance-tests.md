# Conformance test suite

DocDraw conformance is anchored by a test suite: **fixtures + expected outcomes**.

## Artifacts
### Manifest (source of truth)
- `examples/golden-manifest.json`

Defines:
- PASS vs FAIL cases
- source inputs (DocDraw and/or DMP-1)
- expected error codes for FAIL cases
- output targets (normalized + PDFs)

### Source fixtures
- `examples/source/*.docdraw` (canonical inputs)
- `examples/source/*.md` (DMP-1 inputs where applicable)

### Golden outputs (Phase 1: text-only)
- `assets/examples/*.normalized.docdraw`
- `normalized_sha256` stored in the manifest

### Golden outputs (Phase 2: PDFs)
- `assets/examples/*.pdf`
- `pdf_sha256` stored in the manifest

## Phases
### Phase 1 (now)
Conformance covers:
- structural validation (PASS/FAIL + stable error codes)
- deterministic normalization (normalized output + SHA256)

### Phase 2 (implemented)
Conformance adds:
- deterministic PDF rendering (DD-PDF-1) + SHA256

## Tooling
```text
make examples-update
make examples-check
```

`examples-update` regenerates derived artifacts (normalized outputs + PDFs + docs/examples.md).  
`examples-check` fails if generated docs drift from the manifest.

## SHA256 rules
- Hashes are computed over the **exact bytes** of the artifact file.
- Normalized outputs are LF line-ending and end with a trailing newline.


