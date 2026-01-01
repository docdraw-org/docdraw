# CLI (Phase 1 shim)

DocDraw.org includes a minimal CLI shim so the standard feels executable today.

**Binary:** `bin/docdraw`

## Installation
See: [Installation](installation.md)

## Commands
### Validate DocDraw v1
```text
./bin/docdraw validate file.docdraw
```

Machine-readable (DD-CLI-1):

```text
./bin/docdraw --json validate file.docdraw
```

### Normalize DocDraw (deterministic text-only golden)
```text
./bin/docdraw normalize file.docdraw -o file.normalized.docdraw
```

### Convert DMP-1 Markdown → DocDraw
```text
./bin/docdraw convert --from dmp1 file.md -o out.docdraw
```

### Render PDF (DD-PDF-1) (prototype)
This command requires Composer dependencies:

```text
composer install
./bin/docdraw render file.docdraw -o out.pdf
```

## Output formats (DD-CLI-1)
By default, the CLI prints human-readable text.

For integration (DocEdit, scripts), use:
- `--format json` or `--json`

In JSON mode, the CLI prints a single JSON object to stdout with:
- `schema`: `"DD-CLI-1"`
- `ok`: boolean
- `command`, `stage`
- `input`, `output`
- `error` (or `null`)

## Exit codes (stable)
- `0`: success
- `2`: user input invalid (validation/conversion failure)
- `3`: rendering failed
- `1`: internal/tool failure

Notes:
- The converter is intentionally minimal and will evolve; it enforces key DMP-1 rules like “4 spaces per nesting level” and “no tabs.”
- If conversion fails, it prints an error code and line number.

## Platform compatibility
Supported environments:
- Linux (native)
- macOS (native)
- Windows via WSL


