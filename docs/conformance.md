# Conformance

DocDraw.org is a standard; “conformance” is how an implementation proves it follows the spec.

## What it means to conform (v1)
An implementation is considered DocDraw v1 conformant if it:
- parses valid DocDraw v1 input (see: [DocDraw v1](docdraw-v1.md))
- rejects invalid DocDraw v1 input deterministically (no guessing)
- implements Markdown import per DMP-1 if it claims Markdown support (see: [DMP-1](dmp-1.md))
- renders DocDraw v1 according to the PDF rendering contract it claims to follow (see: [DD-PDF-1](pdf-rendering-v1.md))

## Conformance artifacts (MVP)
For DocDraw.org MVP, conformance should be backed by:
- **fixtures**: a small set of input docs
- **expected outcomes**:
  - “parses + renders” fixtures
  - “must fail” fixtures (with expected error codes)

## Next (recommended)
- Add a `conformance/` folder with:
  - `pass/` fixtures
  - `fail/` fixtures
  - expected error JSON for fail cases
- Add “golden PDF” outputs for a subset of pass fixtures.


