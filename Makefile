.PHONY: examples

examples:
	@php scripts/validate_examples.php

.PHONY: examples-check

examples-check:
	@php scripts/check_examples_docs.php
	@php scripts/check_pdf_determinism.php

.PHONY: examples-update

examples-update:
	@php scripts/update_normalized.php
	@php scripts/update_pdfs.php
	@php scripts/generate_examples_docs.php
	@php scripts/validate_examples.php

.PHONY: bundle

bundle:
	@php scripts/build_conformance_bundle.php
	@php scripts/build_spec_bundle.php
	@php scripts/generate_downloads_docs.php

.PHONY: bundle-check

bundle-check:
	@php scripts/check_downloads_docs.php


