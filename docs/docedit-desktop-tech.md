# DocEdit Desktop: tech stack + language suggestions (Windows + macOS)

This document is guidance for choosing a cross‑platform desktop stack for **DocEdit Desktop v0.1**.

DocEdit’s core architectural constraint is simple:

> **The UI is thin. The engine is `docdraw`.**  
> DocEdit calls `docdraw` as a local process and consumes **DD‑CLI‑1 JSON** (`--json`), never human text.

## What matters most (decision criteria)

Prioritize in this order:

1) **Windows + macOS packaging**: one installer per OS, zero user setup (no “install PHP”).
2) **Reliable process execution**: run `docdraw` locally, capture stdout/stderr, handle exit codes.
3) **Editor UX**: syntax highlighting, monospace editing, line numbers, error squiggles.
4) **Fast iteration** for v0.1 (you’re proving value, not building a platform).
5) **Long-term maintainability** (but only after v0.1 is real).

## Recommended “default” choice (fastest path)

### Option A: Tauri (Rust + WebView) + TypeScript UI
- **Best for**: small installers, modern UI, good cross‑platform story, strong security posture.
- **Language**: Rust (shell + app), TypeScript (UI).
- **Pros**:
  - smaller footprint than Electron
  - clean “run a local process” model
  - great for a tool-style desktop app
- **Cons**:
  - Rust learning curve (small but real)
  - WebView differences across OSes (usually manageable)

**How it runs the engine**: spawn `docdraw` (bundled) with `--json`, parse DD‑CLI‑1, render errors/preview.

### Option B: Electron + TypeScript (if you want the lowest friction)
- **Best for**: fastest UI iteration, huge ecosystem, predictable Web runtime.
- **Pros**:
  - easiest cross‑platform dev story
  - many editor components (Monaco, CodeMirror)
  - lots of packaging examples
- **Cons**:
  - larger app size / memory
  - security + updates need discipline

**How it runs the engine**: Node spawns `docdraw --json ...`, parses JSON, opens PDFs.

## Strong “native-ish” alternatives (more serious engineering)

### Option C: .NET (Avalonia UI)
- **Best for**: “native-ish” desktop feel with one codebase.
- **Language**: C#.
- **Pros**:
  - excellent developer productivity for desktop
  - good control over UI and performance
  - strong Windows story, solid macOS story
- **Cons**:
  - packaging and notarization on macOS is doable but takes care
  - smaller ecosystem than Electron for web-like editor components

**Engine integration**: `Process.Start()` `docdraw --json ...` and parse the response.

### Option D: Flutter (Dart)
- **Best for**: polished UI fast, cross‑platform.
- **Pros**:
  - great UI toolkits and performance
  - solid cross‑platform story
- **Cons**:
  - desktop apps are fine, but you’ll do some platform glue for file dialogs/process calls

### Option E: Qt (C++ or PySide/PyQt)
- **Best for**: mature desktop toolkit, “classic” cross‑platform apps.
- **Pros**:
  - proven and powerful
- **Cons**:
  - licensing considerations (depending on usage)
  - C++ complexity (or Python packaging complexity)

## What I would *not* start with for v0.1

- **Two native apps** (Swift/AppKit + WinUI): great long-term polish, but doubles effort immediately.
- **Python desktop app**: possible, but packaging and “works everywhere” is frequently painful.

## Packaging: what you actually ship

DocEdit must bundle:

- the **UI app**
- the **`docdraw` engine**
- a **PHP runtime** for Windows + macOS (v0.1 recommendation: bundle it)
- `vendor/` dependencies needed by `docdraw render` (FPDF)

### Practical packaging shape (v0.1)
- `docdraw` folder inside the app bundle:
  - `bin/docdraw`
  - `vendor/`
  - `src/`
- bundled PHP binary:
  - macOS: `php` inside `.app/Contents/MacOS/`
  - Windows: `php.exe` inside the install directory

DocEdit calls:

```text
<bundled-php> <path-to-docdraw/bin/docdraw> --json render ... 
```

That avoids relying on `#!/usr/bin/env php` being available on Windows.

## Minimal app architecture (recommended)

### UI surfaces
- **Primary surface**: **DocDraw** (canonical)
- **Markdown (DMP‑1)**: v0.1 recommendation is **import/convert**, not an equal-status editor (can be promoted later).

### Pipeline (one button path)
- On “Render PDF”:
  - (if starting from Markdown) `docdraw --json convert --from dmp1 ...`
  - `docdraw --json validate ...`
  - `docdraw --json normalize ...`
  - `docdraw --json render ...`
  - open PDF + show SHA256 + compiler profile

### Error handling
- Always parse `DD‑CLI‑1`:
  - if `ok=false`: show `error.code`, `error.line`, `error.message`
  - use exit codes (`2` vs `3` vs `1`) to decide “user fix” vs “bug”

## Recommendation summary (pick one)

If the goal is **ship v0.1 quickly**:
- **Electron + TypeScript** (fastest dev loop), or
- **Tauri + TypeScript** (smaller + cleaner long-term)

If you want a more “traditional desktop” feel:
- **Avalonia (C#)** is the cleanest single-codebase option.

## Next step

Before writing any UI code, decide:
- **Tauri vs Electron vs Avalonia** (one sentence decision)
- app signing/notarization plan (later)
- where bundled PHP comes from (later)

DocEdit can start as a repo skeleton even while packaging is still “manual”.

## Linux status (suggested wording)
For v0.1:
- Windows + macOS: supported
- Linux: best-effort/unofficial


