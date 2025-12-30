# Roadmap (no dates)

This roadmap describes what ships, not when.

## Phase 1 (shipped): standard backbone
- DocDraw v1 + DMP-1 + DD-PDF-1 docs
- compatibility policy + error registry + AI output contract
- examples manifest (PASS/FAIL) + deterministic normalized goldens + SHA256
- drift-proof docs generation (`make examples-update`, `make examples-check`)

## Phase 2: reference PDF compiler (DD-PDF-1)
- DocDraw → PDF renderer/compiler that:
  - produces PDFs for PASS fixtures into `assets/examples/`
  - computes `pdf_sha256` and updates the manifest
  - declares `compiler_version` and `last_generated`
- golden PDF gallery becomes “real” (compiler-produced, hash-pinned)

## Phase 3: DocEdit integration (local tool)
- local editing + validation + preview loop
- import Markdown (DMP-1) → DocDraw
- export deterministic PDF

## Phase 4: slides profile
- define a renderer profile for slides (e.g. `DD-PPTX-1` or similar)
- extend examples harness to include slide artifacts + hashes


