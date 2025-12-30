# DocDraw.org deliverables (what users get)

This document answers a common question:

> **What can users get from DocDraw.org as an end‑game deliverable?**  
> Is it just the specification, a local converter, a JSON config file…?

The answer depends on **where we are in the rollout**, but the intent is consistent.

## One-sentence answer (safe to publish)
**DocDraw provides a canonical document standard, local validation and conversion tools, and deterministic compilation into final artifacts like PDF — with no required cloud dependency.**

## The end-game deliverables (the “three things”)
End‑game, the ecosystem delivers three concrete outcomes:

1) **A standard you can rely on**  
   - DocDraw v1 (canonical language)
   - DMP‑1 (strict Markdown input profile)
   - DD‑PDF‑1 (renderer profile / output contract)

2) **Tools you can run locally**  
   - CLI tooling (validation, normalization, conversion; later rendering)
   - DocEdit (local UI; planned) that implements the same standard

3) **Deterministic final artifacts**  
   - PDFs (planned via the reference compiler)
   - Later: slides (via a separate renderer profile)

## What users can get today (already real)
### Specifications and contracts
- **DocDraw v1** specification (feature-complete / frozen)
- **DMP‑1** (Markdown input profile)
- **DD‑PDF‑1** (PDF renderer profile / output contract)
- **Compatibility & stability policy**
- **Error code registry**
- **AI output contract**

### Executable tooling (local, today)
From this repo/site, users can already run a local CLI shim:

```text
./bin/docdraw validate file.docdraw
./bin/docdraw normalize file.docdraw
./bin/docdraw convert --from dmp1 file.md
```

This enables:
- local validation (no uploads)
- deterministic normalization (stable output)
- strict Markdown → canonical structure conversion

### Deterministic intermediate artifacts (by design)
Before PDFs exist, we already ship a deterministic artifact:
- **normalized DocDraw output** (`assets/examples/*.normalized.docdraw`)
- with **SHA256 hashes** recorded in `examples/golden-manifest.json`

That means:
- two people running the same input get the same normalized output
- automation/CI can verify conformance early
- PDFs later become “just another compiled artifact”

### Offline, verifiable bundles
DocDraw.org publishes downloadable, checksummed artifacts:
- **Conformance bundle** (fixtures + goldens + harness)
- **Spec release bundle** (v1.0 snapshot)

See: `docs/downloads.md`

## What users will get next (near-term)
### Reference PDF compiler (local)
The missing piece is the **reference compiler** for `DD‑PDF‑1`:

```text
docdraw render file.docdraw -o file.pdf
```

When this exists:
- PDFs are produced deterministically
- `pdf_sha256` is computed and stored in the manifest
- “golden PDFs” become real, compiler-generated outputs (no hand-edited PDFs)

## What users will get as products (separate from the standard)
### DocEdit (planned)
DocEdit will provide a local UI around the same pipeline:
- import from AI / Markdown
- validation with human-readable errors
- preview
- export deterministic PDFs

DocEdit does not replace the standard — it implements it.

### Optional hosted compiler API (planned)
A hosted compiler API may exist later as optional convenience for:
- CI integration
- server-side compilation

But the trust posture remains:
> **You can use DocDraw without ever touching our servers.**

## What DocDraw is not (by design)
DocDraw is intentionally *not*:
- a Google Docs replacement
- a WYSIWYG design/layout tool
- a collaborative editor
- a PDF importer/editor

## The canonical pipeline (mental model)

```text
AI / Markdown / Human Draft
        ↓
   DocDraw (canonical)
        ↓
 Validation + Normalization
        ↓
  Deterministic Compiler
        ↓
    Final Artifact (PDF)
```

Everything we publish fits cleanly into one of these boxes.


