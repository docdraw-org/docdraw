# DocEdit (product plan + build guidance)

DocEdit is a product that **implements** the DocDraw ecosystem. It does not replace the standard — it proves its value.

## Strategy (lock this in)

- **DocEdit proves value.**
- **DocDraw.com scales value** (later, optional).
- **DocDraw.org anchors trust** (already live).

Nothing ships that violates that order.

## Ecosystem map (repos + sites)

DocEdit only works if the ecosystem roles stay clean and non-overlapping.

### Canonical roles (public-facing)

- **DocDraw.org**: the *standard* and *verification artifacts* (specs, conformance suite, downloads).
- **DocEdit.com**: the *product* site (DocEdit Desktop) — marketing, pricing, downloads, support.
- **DocDraw.com** (optional, later): a *service* (API) that scales rendering/automation once the local engine is proven.

### Repos (source-of-truth)

- **`docdraw-org/docdraw`**: source-of-truth for the **standard + local toolchain**:
  - specs (DocDraw v1, DMP‑1, DD‑PDF‑1)
  - conformance suite (`examples/golden-manifest.json`, PASS/FAIL fixtures, goldens + SHA256)
  - engine CLI (`bin/docdraw`) and harness (`make examples-update`, `make examples-check`, bundles)
- **`docdraw-org/website`**: website repo for **DocDraw.org** publishing.
  - pulls/syncs generated docs + artifacts from `docdraw` to avoid drift

DocEdit Desktop should treat `docdraw` as a **compiler dependency** and pin to a known-good release/tag (not “whatever is on main today”).

## What exists today (foundation)

From the `docdraw` repo (and published on DocDraw.org):

- **Standards**: DocDraw v1 (pre-release), DMP‑1, DD‑PDF‑1
- **Conformance**: `examples/golden-manifest.json` + PASS/FAIL fixtures
- **Goldens**: normalized DocDraw outputs + **golden PDFs + `pdf_sha256`**
- **Toolchain**: `./bin/docdraw` + `make examples-update` + `make examples-check`

DocEdit v0.1 must be a **thin UI** over this engine.

## DocEdit Desktop v0.1 (first public, paid release)

### Target audience
- Individuals and small teams who need **sane, deterministic PDFs** for “normal documents” (paragraphs + nested lists).

### Core promise (marketing-safe)
DocEdit is the **final approval layer**: validate structure, normalize deterministically, and render predictable PDFs locally.

### Explicit non-goals (v0.1)
- No cloud sync
- No accounts
- No collaboration
- No WYSIWYG layout editor
- No PDF import-and-edit

### MVP features

- **Desktop app**: Windows + macOS supported (Linux best-effort/unofficial)
- **Local-only execution**
- **Primary surface**: DocDraw (canonical)
- **Markdown (DMP‑1)**: import/convert (v0.1 recommendation; can become a full editing surface later)
- Buttons:
  - Validate
  - Normalize
  - Render PDF
  - (Optional) Convert Markdown → DocDraw
- Error UI:
  - stable `error_code`
  - line number (and column if we later add it)
  - human-readable message
- PDF output:
  - generated locally
  - opened in system viewer

## Architecture (boring on purpose)

### Principle
DocEdit should **never parse human CLI text**. It should consume a stable machine contract.

### Recommended model
- DocEdit UI calls the DocDraw engine via **local process execution**:
  - `docdraw validate`
  - `docdraw normalize`
  - `docdraw render`
  - `docdraw convert --from dmp1` (if Markdown mode is included)
- The engine is treated like a **compiler**:
  - inputs are text
  - outputs are deterministic artifacts + hashes
  - failures are structured diagnostics

## Engine contract: DD-CLI-1 (required for DocEdit)

DocEdit requires a stable JSON output schema and stable exit codes.

### CLI flags

- `--format=text|json` (default: `text` for humans)
- `--json` is an alias for `--format=json`

### JSON schema (DD-CLI-1)

Top-level object:

- `schema`: `"DD-CLI-1"`
- `ok`: boolean
- `command`: string (`validate`, `normalize`, `convert`, `render`)
- `stage`: string (see below)
- `input`: object (paths and/or inline mode)
- `output`: object (paths and sha256 where applicable)
- `error`: `null` or structured error

Optional future-friendly shape (not required for v0.1):
- `output.artifacts[]`: a list of produced artifacts (type/path/sha256) for commands that may generate multiple outputs.

Stages (initial set):
- `docdraw_validation`
- `docdraw_normalization`
- `markdown_import` (DMP‑1)
- `pdf_rendering`

Error object:
- `code`: stable string (e.g. `DDV1_LIST_LEVEL_JUMP`, `DMP1_AMBIGUOUS_LIST_INDENT`)
- `message`: human-readable string (stable meaning; wording can improve)
- `line`: integer (1-based) or `null`
- `hint`: string or `null`

### Example: validate (PASS)

```json
{
  "schema": "DD-CLI-1",
  "ok": true,
  "command": "validate",
  "stage": "docdraw_validation",
  "input": { "path": "file.docdraw" },
  "output": {},
  "error": null
}
```

### Example: validate (FAIL)

```json
{
  "schema": "DD-CLI-1",
  "ok": false,
  "command": "validate",
  "stage": "docdraw_validation",
  "input": { "path": "file.docdraw" },
  "output": {},
  "error": {
    "code": "DDV1_LIST_LEVEL_JUMP",
    "message": "List level jumps from -1 to -3 without an intervening -2.",
    "line": 17,
    "hint": "Change “-3:” to “-2:” or insert a “-2:” item before “-3:”."
  }
}
```

### Exit codes (stable)

DocEdit needs exit codes that map to UI outcomes.

- `0`: success
- `2`: user input invalid (validation / conversion failure; actionable)
- `3`: rendering failed (engine bug or unsupported feature; still actionable)
- `1`: internal/tool failure (unexpected)

DocEdit should treat `2` as “show error inline”, `3` as “show error + bug report CTA”, and `1` as “engine crashed”.

## Desktop tech stack suggestions
See: [DocEdit Desktop tech stack (Windows + macOS)](docedit-desktop-tech.md)

## Packaging (desktop reality)

The hard part is not PHP code — it’s shipping a working engine on Windows/macOS with **zero user setup**.

### Options
- **Bundle PHP runtime + vendor/**:
  - simplest to implement
  - larger app size
  - most predictable
- **PHAR** (single-file engine) + bundled PHP runtime:
  - nicer distribution for the engine itself
  - still requires bundling PHP
- **Native port later** (Go/Rust) if needed:
  - only after the contracts and conformance suite are proven

Initial recommendation: **bundle PHP runtime + engine** for v0.1, optimize later.

## Phased roadmap (DocEdit-first)

### MVP (DocEdit Desktop v0.1)
- Ship the desktop app with Validate/Normalize/Render using the local engine.
- Use DD‑CLI‑1 JSON for diagnostics.

### Phase 1 (after launch)
- Collect demand signal (“can I automate this?”).
- Do not expand the language/spec.

### Phase 2 (optional): DocDraw.com API MVP
- Only after DocEdit demand is proven.
- Single core endpoint: `/render` → returns PDF + `pdf_sha256` + `compiler_version` + `renderer_profile`.

### Phase 3 (optional): DocEdit Online
- Browser UI as convenience, backed by the same engine contract.


