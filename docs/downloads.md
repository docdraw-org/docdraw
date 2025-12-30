# Downloads

## Conformance Bundle v1
The conformance bundle is the canonical fixture set used to verify conformance to **DocDraw v1** and related profiles (like **DD-PDF-1** once PDFs exist).

- **Bundle**: `/assets/downloads/docdraw-conformance-bundle-v1.zip`
- **SHA256**: `c5894703ad75ba64d1fdf1252db8b3f88859228f0e37a6a5c6a3f20a4a369dc0`
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
- **SHA256**: `3a4d493b77a87d3d0a0da8db149f7ae4415f71ed1e48691762a2c61bd4442f17`
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

