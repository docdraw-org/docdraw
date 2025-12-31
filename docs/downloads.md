# Downloads

## Conformance Bundle v1
The conformance bundle is the canonical fixture set used to verify conformance to **DocDraw v1** and related profiles (including **DD-PDF-1**).

- **Bundle**: `/assets/downloads/docdraw-conformance-bundle-v1.zip`
- **SHA256**: `711b34a5c8f31c0e325e4086a56645d14cf4e2123b60a42db075d360558f09b3`
- **SHA file**: `/assets/downloads/docdraw-conformance-bundle-v1.zip.sha256`

### What’s inside
- `examples/golden-manifest.json`
- `examples/source/**`
- `assets/examples/**` (includes normalized + PDF goldens; `pdf_sha256` recorded in the manifest)
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
- **SHA256**: `6d9b1069d87bd2c0f5f582cdbca0de0fa1adc7382bfbd87b342e2d72d605239b`
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

