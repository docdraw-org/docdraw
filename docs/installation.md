# Installation

This installs the **Phase 1 CLI shim** for DocDraw (validation, normalization, and DMP‑1 conversion).

## Requirements
- PHP **8.2+**
- A shell (Linux/macOS/WSL)

## Platform compatibility
The `docdraw` CLI is a PHP-based command-line tool.

Supported environments:
- Linux (native)
- macOS (native)
- Windows via WSL (Windows Subsystem for Linux)

## Install (via git)
Clone the repo and make the CLI executable:

```text
git clone https://github.com/docdraw-org/docdraw.git
cd docdraw
chmod +x bin/docdraw
```

## Optional: install PDF rendering dependencies
Only needed for `docdraw render`:

```text
composer install
```

## Verify
```text
./bin/docdraw --help
```

## How to run
You can either:
- run `./bin/docdraw` directly, or
- add `bin/` to your PATH and run `docdraw`

Example:

```text
./bin/docdraw validate examples/source/quickstart.docdraw
```

## Status note
PDF rendering (`docdraw render`) is **not implemented yet**.  
Validation, normalization, and DMP‑1 conversion are implemented.

## Source / issues
- Repo: `https://github.com/docdraw-org/docdraw`
- Issues: `https://github.com/docdraw-org/docdraw/issues`


