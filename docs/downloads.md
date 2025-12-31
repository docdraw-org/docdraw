# Downloads

## Conformance Bundle v1
The conformance bundle is the canonical fixture set used to verify conformance to **DocDraw v1** and related profiles (like **DD-PDF-1** once PDFs exist).

- **Bundle**: `/assets/downloads/docdraw-conformance-bundle-v1.zip`
- **SHA256**: `4172080cf990c0844b4974ed3e6b6aaeddf90a5c8fde34dc8241239c6742e2db`
- **SHA file**: `/assets/downloads/docdraw-conformance-bundle-v1.zip.sha256`

### What’s inside
- `examples/golden-manifest.json`
- `examples/source/**`
- `assets/examples/**` (includes normalized goldens today; PDFs may be absent for now)
- `CONTRIBUTING.md`
- `README.md` (bundle usage)

### Verify integrity
Download the bundle and verify the checksum:

```text
sha256sum -c docdraw-conformance-bundle-v1.zip.sha256
```

## Spec Release Bundle v1.0
A snapshot release artifact for the **DocDraw v1.0** standard (docs + examples + goldens).

- **Bundle**: `/assets/downloads/docdraw-spec-bundle-v1.0.zip`
- **SHA256**: `f39e6da591ae16895199582d98ab652118bede8ee2bea8e1877d81735d3de7c1`
- **SHA file**: `/assets/downloads/docdraw-spec-bundle-v1.0.zip.sha256`

### Verify integrity
```text
sha256sum -c docdraw-spec-bundle-v1.0.zip.sha256
```

### Minimal usage (in repo)
```text
make examples-update
make examples-check
```

