# Golden examples

> This page is generated from `examples/golden-manifest.json`. Do not edit by hand.

Golden examples are the “trust builder” for a standard: **input + deterministic compiler + expected output** that implementations can compare against.

## Phase 2 (live): golden PDFs + hashes
These examples now include **compiler-produced golden PDFs** with a recorded `pdf_sha256` in the manifest.

- **Do not edit PDFs by hand.** Regenerate via the toolchain.
- Each example also includes a deterministic **text-only golden** (`*.normalized.docdraw`) + SHA256.

Source of truth:
- `examples/golden-manifest.json`
- `examples/source/` (inputs)
- `assets/examples/` (compiler-produced PDFs + normalized outputs)

## Examples

### Quickstart

- **id**: `quickstart`
- **expected**: PASS
- **DocDraw input**: [`examples/source/quickstart.docdraw`](/examples/source/quickstart.docdraw)
- **DMP-1 input**: [`examples/source/quickstart.md`](/examples/source/quickstart.md)
- **Golden PDF**: [`/assets/examples/quickstart.pdf`](/assets/examples/quickstart.pdf)
- **renderer_profile**: `DD-PDF-1`
- **Normalized golden**: [`/assets/examples/quickstart.normalized.docdraw`](/assets/examples/quickstart.normalized.docdraw)
- **normalized_sha256**: `c3e3cace236789fe8b917df7ed262e4478467f68169dfa7b4e4f6dcc18bf7808`
- **compiler_version**: `docdraw-php-ddpdf1-proto`
- **pdf_sha256**: `da88434f184f5b67da0f9313bd269510cf990e3276f6edae8d41b2d8ac631562`
- **last_generated**: `TBD`
- **Expected Output Contract**:
  - MUST: Bullets render with explicit nesting from `-L` levels.
  - MUST: Continuation lines (`..:`) align to the list text start and MUST NOT introduce extra vertical spacing.
  - MUST: Wrapped list lines use hanging indent alignment (text start X).
  - SHOULD: Headings keep-with-next (avoid orphan headings).
  - MUST: Divider (`---`) renders as a horizontal rule with consistent spacing above and below.
  - MUST: Rendering is deterministic: same input yields same PDF.

### List wrapping & continuations

- **id**: `list-wrapping-and-continuations`
- **expected**: PASS
- **DocDraw input**: [`examples/source/list-wrapping-and-continuations.docdraw`](/examples/source/list-wrapping-and-continuations.docdraw)
- **DMP-1 input**: [`examples/source/list-wrapping-and-continuations.md`](/examples/source/list-wrapping-and-continuations.md)
- **Golden PDF**: [`/assets/examples/list-wrapping-and-continuations.pdf`](/assets/examples/list-wrapping-and-continuations.pdf)
- **renderer_profile**: `DD-PDF-1`
- **Normalized golden**: [`/assets/examples/list-wrapping-and-continuations.normalized.docdraw`](/assets/examples/list-wrapping-and-continuations.normalized.docdraw)
- **normalized_sha256**: `1d13ac9c6aa55d5e7b2b9811720917f6a1480d24cc1094935d569074b8e991c5`
- **compiler_version**: `docdraw-php-ddpdf1-proto`
- **pdf_sha256**: `f5567f226f5b0ae85ad2849d347c501af212e2c87e81cf3d9972a9b574b7dc93`
- **last_generated**: `TBD`
- **Expected Output Contract**:
  - MUST: Long list items wrap cleanly without changing indentation.
  - MUST: Continuation lines remain part of the same list item and align with the text start.
  - MUST: Nested bullets indent exactly one level relative to their parent.
  - MUST NOT: Introduce unexpected vertical gaps inside a list block.

### Mixed lists & numbering restart

- **id**: `mixed-lists-and-numbering`
- **expected**: PASS
- **DocDraw input**: [`examples/source/mixed-lists-and-numbering.docdraw`](/examples/source/mixed-lists-and-numbering.docdraw)
- **DMP-1 input**: [`examples/source/mixed-lists-and-numbering.md`](/examples/source/mixed-lists-and-numbering.md)
- **Golden PDF**: [`/assets/examples/mixed-lists-and-numbering.pdf`](/assets/examples/mixed-lists-and-numbering.pdf)
- **renderer_profile**: `DD-PDF-1`
- **Normalized golden**: [`/assets/examples/mixed-lists-and-numbering.normalized.docdraw`](/assets/examples/mixed-lists-and-numbering.normalized.docdraw)
- **normalized_sha256**: `26536ae2d191305e0d820fb5d6e43050512333ac5d73ff0077fdb6c115675e4b`
- **compiler_version**: `docdraw-php-ddpdf1-proto`
- **pdf_sha256**: `f103807afe66f6136812fc38319ca9d046b64c0977f56df8ebf7982f1dee5382`
- **last_generated**: `TBD`
- **Expected Output Contract**:
  - MUST: Ordered list numbering restarts after a heading (new ordered list block).
  - MUST: Nested bullets render at the correct explicit levels and wrap with hanging indent alignment.
  - MUST: Continuation text remains attached to the prior list item and aligns to text start.
  - SHOULD: Pagination does not orphan headings; list indentation and numbering remain consistent across page breaks.

### Ordered markers: lower-alpha-paren

- **id**: `ordered-markers-alpha-paren`
- **expected**: PASS
- **DocDraw input**: [`examples/source/ordered-markers-alpha-paren.docdraw`](/examples/source/ordered-markers-alpha-paren.docdraw)
- **Golden PDF**: [`/assets/examples/ordered-markers-alpha-paren.pdf`](/assets/examples/ordered-markers-alpha-paren.pdf)
- **renderer_profile**: `DD-PDF-1`
- **Normalized golden**: [`/assets/examples/ordered-markers-alpha-paren.normalized.docdraw`](/assets/examples/ordered-markers-alpha-paren.normalized.docdraw)
- **normalized_sha256**: `58969cc99ab6cd2796658fddc422264af1f5f88c8eac154afeaebae9a706bbfd`
- **compiler_version**: `docdraw-php-ddpdf1-proto`
- **pdf_sha256**: `0858f7eb8941ee2c573661604756278238808e634cea971aa81881445c5c9b98`
- **last_generated**: `TBD`
- **Expected Output Contract**:
  - MUST: Ordered list markers render as (a) (b) (c) ... when renderer option ol_style=lower-alpha-paren is used.
  - MUST: Marker sequence continues beyond z as aa, ab, ... deterministically.
  - MUST: Wrapping/indent behavior matches DD-PDF-1 list rules.

### DMP-1 ambiguous indent (FAIL)

- **id**: `dmp1-ambiguous-indent`
- **expected**: FAIL (`markdown_import`) codes: `AMBIGUOUS_LIST_INDENT`
- **DMP-1 input**: [`examples/source/dmp1-ambiguous-indent.md`](/examples/source/dmp1-ambiguous-indent.md)
- **compiler_version**: `TBD`
- **pdf_sha256**: `TBD`
- **last_generated**: `TBD`
- **Expected Output Contract**:
  - MUST: Converter fails (no guessing) because list indentation does not increase by exactly 4 spaces per level.

### DocDraw invalid list level jump (FAIL)

- **id**: `docdraw-invalid-list-level-jump`
- **expected**: FAIL (`docdraw_validation`) codes: `DDV1_LIST_LEVEL_JUMP`
- **DocDraw input**: [`examples/source/docdraw-invalid-list-level-jump.docdraw`](/examples/source/docdraw-invalid-list-level-jump.docdraw)
- **compiler_version**: `TBD`
- **pdf_sha256**: `TBD`
- **last_generated**: `TBD`
- **Expected Output Contract**:
  - MUST: Validator fails because list level jumps from `-1` to `-3` without an intervening `-2`.
  - MUST: Validator reports a stable error code (e.g. `DDV1_LIST_LEVEL_JUMP`) and a fix suggestion.

## Reproduce
In the `docdraw` repo:

```text
make examples-update
make examples-check
```

