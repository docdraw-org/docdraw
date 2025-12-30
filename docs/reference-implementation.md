# Reference implementation status

This page clarifies what exists today versus what is planned, so implementers and users don’t have to guess.

## What exists today (implemented)
- **DocDraw.org spec docs** (DocDraw v1, DMP-1, DD-PDF-1, examples, compatibility policy)
- **Examples system**:
  - `examples/golden-manifest.json` is the source of truth
  - generated `docs/examples.md`
  - PASS/FAIL examples with expected error codes
- **Deterministic text-only goldens**:
  - normalized DocDraw outputs under `assets/examples/*.normalized.docdraw`
  - SHA256 hashes recorded in the manifest
- **Tooling**:
  - `make examples-update` (regenerate normalized goldens + regenerate examples docs + validate manifest)
  - `make examples-check` (fail if docs/examples.md is out-of-date)

## What is planned next (not implemented yet)
- **Reference compiler** for PDF output:
  - DocDraw → PDF compilation
  - updates `examples/golden-manifest.json` with:
    - `compiler_version`
    - `pdf_sha256`
    - `last_generated`
  - writes PDFs under `assets/examples/*.pdf`

## What “reference” means
When we say “reference implementation/compiler,” we mean:
- deterministic behavior (same input → same output)
- conforms to **DocDraw v1** and **DMP-1**
- renders PDFs according to **DD-PDF-1**
- produces outputs that match the published goldens (hashes)

## Explicit non-goals (v1)
- PDF import or “edit arbitrary PDFs”
- claims of perfect conversion from arbitrary PDFs to DocDraw
- full Word/Docs replacement
- cloud storage of documents (DocDraw.org is a standard site)


