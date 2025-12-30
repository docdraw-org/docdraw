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

### Normalize DocDraw (deterministic text-only golden)
```text
./bin/docdraw normalize file.docdraw -o file.normalized.docdraw
```

### Convert DMP-1 Markdown → DocDraw
```text
./bin/docdraw convert --from dmp1 file.md -o out.docdraw
```

Notes:
- The converter is intentionally minimal and will evolve; it enforces key DMP-1 rules like “4 spaces per nesting level” and “no tabs.”
- If conversion fails, it prints an error code and line number.

## Platform compatibility
Supported environments:
- Linux (native)
- macOS (native)
- Windows via WSL


